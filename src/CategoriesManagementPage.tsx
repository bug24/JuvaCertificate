import { useEffect, useMemo, useState, type FormEvent } from "react";
import { ArrowDown, ArrowUp, ChevronDown, FileBadge, Layers3, Plus, Save, Search, ShieldCheck } from "lucide-react";
import "./CategoriesManagementPage.css";

type ApiResponse<T> = { data: T | null; error: string | null };
type ApiRequest = <T>(path: string, options?: RequestInit, csrf?: string) => Promise<ApiResponse<T>>;
type Row = Record<string, any>;
type Section = "details" | "settings" | "fields" | "tables" | "publish";
type Props = { csrf: string; request: ApiRequest; isSuperAdmin?: boolean };

const fieldTypes = ["text", "textarea", "number", "date", "select", "checkbox", "pass_fail", "photo", "signature"];
const columnTypes = fieldTypes.filter((type) => !["photo", "signature"].includes(type));
const mimes = [["JPG / JPEG", "image/jpeg"], ["PNG", "image/png"], ["WebP", "image/webp"], ["PDF", "application/pdf"]];
const emptyCategory = { code: "", short_code: "", name: "", description: "", validity_months: "12", status: "active", requires_review: true };
const emptyField = { field_key: "", label: "", help_text: "", placeholder_text: "", field_type: "text", is_required: false, appears_on_pdf: true, pdf_section: "summary", sort_order: "100", options: "" };
const emptyTable = { section_key: "", label: "", help_text: "", min_rows: "0", max_rows: "", pdf_section: "checklist", sort_order: "100" };
const emptyColumn = { section_id: "", column_key: "", label: "", column_type: "text", is_required: false, appears_on_pdf: true, sort_order: "100", options: "" };

function Panel({ title, action, children }: { title: string; action?: React.ReactNode; children: React.ReactNode }) {
  return <section className="panel"><header className="panel-header"><h2>{title}</h2>{action}</header>{children}</section>;
}
function keyFrom(value: string) { return value.trim().toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/^_+|_+$/g, "").slice(0, 100); }
function options(value: string) { const list = value.split(",").map((item) => item.trim()).filter(Boolean); return list.length ? list : null; }
function categoryForm(item: Row) {
  return { code: item.code || "", short_code: item.short_code || "", name: item.name || "", description: item.description || "", validity_months: String(item.validity_months || 12), status: item.status || "active", requires_review: Number(item.requires_review ?? 1) === 1 };
}

export function CategoriesManagementPage({ csrf, request, isSuperAdmin = false }: Props) {
  const [categories, setCategories] = useState<Row[]>([]);
  const [selected, setSelected] = useState<Row | null>(null);
  const [template, setTemplate] = useState<Row | null>(null);
  const [fields, setFields] = useState<Row[]>([]);
  const [tables, setTables] = useState<Row[]>([]);
  const [edit, setEdit] = useState(emptyCategory);
  const [create, setCreate] = useState(emptyCategory);
  const [field, setField] = useState(emptyField);
  const [table, setTable] = useState(emptyTable);
  const [column, setColumn] = useState(emptyColumn);
  const [section, setSection] = useState<Section>("details");
  const [filter, setFilter] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  const visible = useMemo(() => {
    const term = filter.trim().toLowerCase();
    return categories.filter((item) => (statusFilter === "all" || item.status === statusFilter) && (!term || [item.name, item.code, item.short_code, item.status].some((value) => String(value || "").toLowerCase().includes(term))));
  }, [categories, filter, statusFilter]);
  const groups = useMemo(() => {
    const map = new Map<string, Row[]>();
    fields.forEach((item) => { const name = String(item.pdf_section || "Other fields").replaceAll("_", " "); map.set(name, [...(map.get(name) || []), item]); });
    return [...map.entries()];
  }, [fields]);

  async function choose(item: Row) {
    setSelected(item); setEdit(categoryForm(item)); setError("");
    const result = await request<{ template: Row | null; fields: Row[]; sections: Row[] }>(`/categories/fields.php?category_id=${item.id}`);
    if (result.error) { setError(result.error); return; }
    setTemplate(result.data?.template || null); setFields(result.data?.fields || []); setTables(result.data?.sections || []);
  }
  async function load(id?: number) {
    const result = await request<{ categories: Row[] }>("/categories/index.php");
    if (result.error) { setError(result.error); return; }
    const rows = result.data?.categories || []; setCategories(rows);
    const next = rows.find((item) => Number(item.id) === Number(id || selected?.id)) || rows[0];
    if (next) await choose(next);
  }
  useEffect(() => { void load(); }, []);

  async function createCategory(event: FormEvent) {
    event.preventDefault(); setError(""); setMessage("");
    const result = await request<{ category: Row }>("/categories/index.php", { method: "POST", body: JSON.stringify({ ...create, code: create.code || create.short_code, validity_months: Number(create.validity_months) }) }, csrf);
    if (result.error) { setError(result.error); return; }
    setCreate(emptyCategory); setMessage("Category created with a safe starter form."); await load(Number(result.data?.category?.id));
  }
  async function saveDetails() {
    if (!selected) return;
    const result = await request(`/categories/category.php?id=${selected.id}`, { method: "PATCH", body: JSON.stringify({ code: edit.code, short_code: edit.short_code, name: edit.name, description: edit.description, validity_months: Number(edit.validity_months), requires_review: edit.requires_review }) }, csrf);
    if (result.error) { setError(result.error); return; }
    setMessage("Category details saved. Renderer and schema metadata were preserved."); await load(Number(selected.id));
  }
  async function changeStatus(nextStatus: "active" | "inactive" | "legacy") {
    if (!selected || nextStatus === selected.status) return;
    const dependencies = `${selected.inspection_count || 0} inspections, ${selected.certificate_count || 0} certificates, ${selected.draft_count || 0} drafts, active template v${selected.current_version || 1}`;
    const action = nextStatus === "active" ? "Enable" : nextStatus === "inactive" ? "Disable" : "Mark as Legacy";
    if (!window.confirm(`${action} ${selected.name}?\n\nDependencies: ${dependencies}\n\nExisting inspections, certificates, PDFs and verification links will remain available.`)) return;
    let reason = "";
    if (nextStatus === "legacy" || selected.status === "legacy" || (nextStatus === "inactive" && Number(selected.inspection_count || 0) > 0)) {
      reason = window.prompt("Reason for this category status change")?.trim() || "";
      if (!reason) { setError("A reason is required for this status change."); return; }
    }
    const result = await request<{ category: Row }>("/categories/status.php", { method: "POST", body: JSON.stringify({ id: Number(selected.id), status: nextStatus, reason }) }, csrf);
    if (result.error) { setError(result.error); return; }
    setMessage(`${selected.name} is now ${nextStatus}. Historical records remain available.`);
    await load(Number(selected.id));
  }
  async function deleteUnusedCategory() {
    if (!selected || !isSuperAdmin) return;
    if (!window.confirm(`Permanently delete unused category ${selected.name}? This is allowed only when it has no inspections, certificates, or used form versions.`)) return;
    const result = await request("/categories/delete-unused.php", { method: "POST", body: JSON.stringify({ id: Number(selected.id) }) }, csrf);
    if (result.error) { setError(result.error); return; }
    setSelected(null); setMessage(`${selected.name} was permanently deleted.`); await load();
  }
  async function saveSettings() {
    if (!selected || !template) return;
    const keys = ["show_inspector_signature", "show_authenticator_signature", "show_company_stamp", "requires_evidence", "requires_inspection_photo", "requires_inspector_signature", "requires_authenticator_signature", "requires_company_stamp"];
    const payload: Row = { category_id: Number(selected.id), minimum_evidence_files: Number(template.minimum_evidence_files || 0), allowed_evidence_types: template.allowed_evidence_types || "image/jpeg,image/png,image/webp,application/pdf" };
    keys.forEach((key) => { payload[key] = template[key]; });
    const result = await request<{ template: Row; message: string }>("/categories/template-settings.php", { method: "POST", body: JSON.stringify(payload) }, csrf);
    if (result.error) { setError(result.error); return; }
    if (result.data?.template) setTemplate(result.data.template); setMessage(result.data?.message || "Certificate settings saved.");
  }
  async function addField(event: FormEvent) {
    event.preventDefault(); if (!selected) return;
    const result = await request(`/categories/fields.php?category_id=${selected.id}`, { method: "POST", body: JSON.stringify({ ...field, field_key: field.field_key || keyFrom(field.label), sort_order: Number(field.sort_order), options: options(field.options) }) }, csrf);
    if (result.error) { setError(result.error); return; }
    setField(emptyField); setMessage("Field added. Its system key is now protected."); await choose(selected);
  }
  async function moveField(item: Row, direction: -1 | 1) {
    const ordered = [...fields].sort((a, b) => Number(a.sort_order) - Number(b.sort_order) || Number(a.id) - Number(b.id));
    const index = ordered.findIndex((row) => Number(row.id) === Number(item.id)); const other = ordered[index + direction]; if (!other) return;
    const results = await Promise.all([
      request(`/categories/field.php?id=${item.id}`, { method: "PATCH", body: JSON.stringify({ sort_order: Number(other.sort_order) }) }, csrf),
      request(`/categories/field.php?id=${other.id}`, { method: "PATCH", body: JSON.stringify({ sort_order: Number(item.sort_order) }) }, csrf),
    ]);
    const failed = results.find((result) => result.error); if (failed?.error) { setError(failed.error); return; }
    setMessage("Field order updated."); if (selected) await choose(selected);
  }
  async function addTable(event: FormEvent) {
    event.preventDefault(); if (!selected) return;
    const result = await request(`/categories/sections.php?category_id=${selected.id}`, { method: "POST", body: JSON.stringify({ ...table, section_key: table.section_key || keyFrom(table.label), min_rows: Number(table.min_rows || 0), max_rows: table.max_rows ? Number(table.max_rows) : null, sort_order: Number(table.sort_order) }) }, csrf);
    if (result.error) { setError(result.error); return; }
    setTable(emptyTable); setMessage("Repeatable table added. Its mapping key is now protected."); await choose(selected);
  }
  async function addColumn(event: FormEvent) {
    event.preventDefault(); if (!selected || !column.section_id) return;
    const result = await request("/categories/section.php", { method: "POST", body: JSON.stringify({ ...column, id: Number(column.section_id), action: "add_column", column_key: column.column_key || keyFrom(column.label), sort_order: Number(column.sort_order), options: options(column.options) }) }, csrf);
    if (result.error) { setError(result.error); return; }
    setColumn({ ...emptyColumn, section_id: column.section_id }); setMessage("Table column added. Its mapping key is now protected."); await choose(selected);
  }
  async function publish() {
    if (!selected || !window.confirm(`Publish a new form version for ${selected.name}? Historical records will remain unchanged.`)) return;
    const result = await request("/categories/publish-version.php", { method: "POST", body: JSON.stringify({ category_id: selected.id }) }, csrf);
    if (result.error) { setError(result.error); return; }
    setMessage("New active form version published."); await load(Number(selected.id));
  }
  function toggleMime(mime: string, checked: boolean) {
    const current = String(template?.allowed_evidence_types || "").split(",").map((item) => item.trim()).filter(Boolean);
    const next = checked ? [...new Set([...current, mime])] : current.filter((item) => item !== mime);
    setTemplate({ ...(template || {}), allowed_evidence_types: next.join(",") });
  }

  const tabs: Array<[Section, string]> = [["details", "Category Details"], ["settings", "Certificate Settings"], ["fields", "Inspection Fields"], ["tables", "Repeatable Tables"], ["publish", "Preview & Publish"]];
  const switchControl = (key: string, title: string, note?: string) => <label className="switch-row"><span><b>{title}</b>{note && <small>{note}</small>}</span><input type="checkbox" checked={Number(template?.[key] ?? 0) === 1} onChange={(event) => setTemplate({ ...(template || {}), [key]: event.target.checked ? 1 : 0 })} /></label>;

  return <>
    <div className="page-heading"><div><p className="eyebrow">CERTIFICATION SETUP</p><h1>Categories & forms</h1><span>Manage inspection categories and the information collected for each certificate.</span></div></div>
    {error && <div className="inline-alert form-error">{error}</div>}{message && <div className="inline-alert form-success">{message}</div>}
    <div className="category-library-shell">
      <Panel title="Category library" action={<label className="builder-search"><Search size={15} /><input value={filter} onChange={(event) => setFilter(event.target.value)} placeholder="Search by name or code" /></label>}>
        <div className="category-status-filters">{["all","active","inactive","legacy"].map((value) => <button type="button" key={value} className={statusFilter === value ? "active" : ""} onClick={() => setStatusFilter(value)}>{value === "all" ? "All Categories" : value.toUpperCase()}</button>)}</div>
        <div className="category-list category-library-list">{visible.map((item) => <button type="button" key={item.id} className={selected?.id === item.id ? "selected" : ""} onClick={() => void choose(item)}><span>{item.short_code || item.code}</span><strong>{item.name}</strong><small><b className={`category-status-badge ${item.status}`}>{String(item.status).toUpperCase()}</b> {item.validity_months} months / v{item.current_version || 1} / {item.field_count || 0} fields / {item.section_count || 0} tables</small></button>)}</div>
      </Panel>
      <details className="safe-create-disclosure"><summary><Plus size={16} />Add a new category</summary><form className="record-form setup-form" onSubmit={createCategory}>
        <label>Category name<input required value={create.name} onChange={(event) => setCreate({ ...create, name: event.target.value })} /></label>
        <label>Short code<input required value={create.short_code} onChange={(event) => { const value = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, ""); setCreate({ ...create, short_code: value, code: value }); }} /></label>
        <label>Validity period (months)<input type="number" min="1" max="120" value={create.validity_months} onChange={(event) => setCreate({ ...create, validity_months: event.target.value })} /></label>
        <label>Status<select value={create.status} onChange={(event) => setCreate({ ...create, status: event.target.value })}><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
        <label className="wide-field">Description<textarea value={create.description} onChange={(event) => setCreate({ ...create, description: event.target.value })} /></label>
        <label className="check-label builder-check"><input type="checkbox" checked={create.requires_review} onChange={(event) => setCreate({ ...create, requires_review: event.target.checked })} />Require reviewer approval</label>
        <button className="primary-button"><Plus size={16} />Create category</button>
      </form></details>
    </div>

    {selected && <div className="category-management-shell">
      <div className="category-management-heading"><div><span>{selected.short_code || selected.code}</span><div><h2>{selected.name}</h2><p>Active form v{template?.version || selected.current_version || 1} Â· {fields.length} fields Â· {tables.length} repeatable tables</p></div></div><b className={`status-pill ${selected.status}`}>{selected.status}</b></div>
      <nav className="category-section-tabs">{tabs.map(([id, label]) => <button type="button" key={id} className={section === id ? "active" : ""} onClick={() => setSection(id)}>{label}</button>)}</nav>

      {section === "details" && <Panel title="Category details" action={<button className="secondary-button" type="button" onClick={() => void saveDetails()}><Save size={16} />Save details</button>}><div className="record-form category-metadata-form">
        <label>Category name<input value={edit.name} onChange={(event) => setEdit({ ...edit, name: event.target.value })} /></label><label>Short code<input value={edit.short_code} onChange={(event) => setEdit({ ...edit, short_code: event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "") })} /></label>
        <label>Internal category code<input value={edit.code} onChange={(event) => setEdit({ ...edit, code: event.target.value.toUpperCase() })} /></label><label>Validity period (months)<input type="number" min="1" max="120" value={edit.validity_months} onChange={(event) => setEdit({ ...edit, validity_months: event.target.value })} /></label>
        <div className="category-lifecycle-control"><span>Category status</span><b className={`category-status-badge ${selected.status}`}>{String(selected.status).toUpperCase()}</b><small>{selected.inspection_count || 0} inspections / {selected.certificate_count || 0} certificates / {selected.draft_count || 0} drafts / active template v{selected.current_version || 1}</small><div className="row-actions">{selected.status !== "active" && <button type="button" className="secondary-button" onClick={() => void changeStatus("active")}>Enable Category</button>}{selected.status === "active" && <button type="button" className="secondary-button" onClick={() => void changeStatus("inactive")}>Disable Category</button>}{isSuperAdmin && selected.status !== "legacy" && <button type="button" className="secondary-button" onClick={() => void changeStatus("legacy")}>Mark as Legacy</button>}{isSuperAdmin && selected.status === "legacy" && <button type="button" className="secondary-button" onClick={() => void changeStatus("inactive")}>Restore as Inactive</button>}{isSuperAdmin && Number(selected.inspection_count || 0) === 0 && Number(selected.certificate_count || 0) === 0 && Number(selected.draft_count || 0) === 0 && <button type="button" className="danger-button" onClick={() => void deleteUnusedCategory()}>Delete Unused Category</button>}</div></div><label className="check-label builder-check"><input type="checkbox" checked={edit.requires_review} onChange={(event) => setEdit({ ...edit, requires_review: event.target.checked })} />Require reviewer approval</label>
        <label className="wide-field">Description<textarea value={edit.description} onChange={(event) => setEdit({ ...edit, description: event.target.value })} /></label>
      </div></Panel>}

      {section === "settings" && <Panel title="Certificate settings" action={<button className="secondary-button" type="button" onClick={() => void saveSettings()}><Save size={16} />Save settings</button>}><div className="settings-groups">
        <div className="settings-group"><h3>Certificate marks</h3>{switchControl("show_inspector_signature", "Inspector signature", "Show the inspector signature on the certificate.")}{switchControl("show_authenticator_signature", "Authenticator signature", "Show the approving signature.")}{switchControl("show_company_stamp", "Company stamp", "Show JUVA's active stamp.")}</div>
        <div className="settings-group"><h3>Issuance requirements</h3>{switchControl("requires_evidence", "Evidence required")}{switchControl("requires_inspection_photo", "Inspection photo required")}{switchControl("requires_inspector_signature", "Inspector signature required")}{switchControl("requires_authenticator_signature", "Authenticator signature required")}{switchControl("requires_company_stamp", "Company stamp required")}</div>
        <div className="settings-group"><h3>Evidence files</h3><label>Minimum files<input type="number" min="0" max="20" value={template?.minimum_evidence_files ?? 0} onChange={(event) => setTemplate({ ...(template || {}), minimum_evidence_files: event.target.value })} /></label><div className="format-checks"><span>Allowed formats</span>{mimes.map(([label, mime]) => <label key={mime}><input type="checkbox" checked={String(template?.allowed_evidence_types || "").split(",").includes(mime)} onChange={(event) => toggleMime(mime, event.target.checked)} />{label}</label>)}</div></div>
      </div></Panel>}

      {section === "fields" && <><Panel title="Add inspection field"><form className="record-form category-field-form safe-builder-form" onSubmit={addField}>
        <label>Field label<input required value={field.label} onChange={(event) => setField({ ...field, label: event.target.value, field_key: keyFrom(event.target.value) })} /></label><label>Input type<select value={field.field_type} onChange={(event) => setField({ ...field, field_type: event.target.value })}>{fieldTypes.map((type) => <option key={type}>{type}</option>)}</select></label>
        <label>Form section<input value={field.pdf_section} onChange={(event) => setField({ ...field, pdf_section: keyFrom(event.target.value) })} /></label><label>Placeholder<input value={field.placeholder_text} onChange={(event) => setField({ ...field, placeholder_text: event.target.value })} /></label>
        {["select", "checkbox"].includes(field.field_type) && <label className="wide-field">Choices<input value={field.options} onChange={(event) => setField({ ...field, options: event.target.value })} placeholder="Pass, Fail, N/A" /></label>}<label className="wide-field">Help text<input value={field.help_text} onChange={(event) => setField({ ...field, help_text: event.target.value })} /></label>
        <label className="check-label builder-check"><input type="checkbox" checked={field.is_required} onChange={(event) => setField({ ...field, is_required: event.target.checked })} />Required</label><label className="check-label builder-check"><input type="checkbox" checked={field.appears_on_pdf} onChange={(event) => setField({ ...field, appears_on_pdf: event.target.checked })} />Show on certificate</label><button className="primary-button"><Plus size={16} />Add field</button>
      </form></Panel><Panel title="Inspection fields"><div className="field-groups">{groups.map(([name, rows]) => <section className="field-group" key={name}><header><h3>{name}</h3><span>{rows.length} fields</span></header>{rows.map((item, index) => <article className="field-row" key={item.id}><div><strong>{item.label}</strong><span>{item.field_type.replace("_", " / ")}{Number(item.is_required) ? " Â· required" : ""}{Number(item.appears_on_pdf ?? 1) ? " Â· certificate" : ""}</span>{item.help_text && <small>{item.help_text}</small>}</div><div className="icon-action-row"><button title="Move up" disabled={index === 0} onClick={() => void moveField(item, -1)}><ArrowUp size={16} /></button><button title="Move down" disabled={index === rows.length - 1} onClick={() => void moveField(item, 1)}><ArrowDown size={16} /></button></div></article>)}</section>)}</div><div className="safe-operation-note"><ShieldCheck size={17} />Existing fields cannot be deleted here because their keys may be used by saved inspections and issued certificates.</div></Panel></>}

      {section === "tables" && <><div className="builder-two-column"><Panel title="Add repeatable table"><form className="record-form category-section-form safe-builder-form" onSubmit={addTable}><label>Table name<input required value={table.label} onChange={(event) => setTable({ ...table, label: event.target.value, section_key: keyFrom(event.target.value) })} /></label><label>Minimum rows<input type="number" min="0" value={table.min_rows} onChange={(event) => setTable({ ...table, min_rows: event.target.value })} /></label><label>Maximum rows<input type="number" min="1" value={table.max_rows} onChange={(event) => setTable({ ...table, max_rows: event.target.value })} /></label><label className="wide-field">Help text<input value={table.help_text} onChange={(event) => setTable({ ...table, help_text: event.target.value })} /></label><button className="primary-button"><Layers3 size={16} />Add table</button></form></Panel>
        <Panel title="Add table column"><form className="record-form category-section-form safe-builder-form" onSubmit={addColumn}><label>Table<select required value={column.section_id} onChange={(event) => setColumn({ ...column, section_id: event.target.value })}><option value="">Select table</option>{tables.map((item) => <option key={item.id} value={item.id}>{item.label}</option>)}</select></label><label>Column label<input required value={column.label} onChange={(event) => setColumn({ ...column, label: event.target.value, column_key: keyFrom(event.target.value) })} /></label><label>Input type<select value={column.column_type} onChange={(event) => setColumn({ ...column, column_type: event.target.value })}>{columnTypes.map((type) => <option key={type}>{type}</option>)}</select></label>{["select", "checkbox"].includes(column.column_type) && <label>Choices<input value={column.options} onChange={(event) => setColumn({ ...column, options: event.target.value })} /></label>}<label className="check-label builder-check"><input type="checkbox" checked={column.is_required} onChange={(event) => setColumn({ ...column, is_required: event.target.checked })} />Required</label><label className="check-label builder-check"><input type="checkbox" checked={column.appears_on_pdf} onChange={(event) => setColumn({ ...column, appears_on_pdf: event.target.checked })} />Show on certificate</label><button className="primary-button"><Plus size={16} />Add column</button></form></Panel></div>
        <Panel title="Repeatable tables"><div className="repeatable-section-list">{tables.length === 0 && <div className="empty-state compact-empty"><Layers3 size={26} /><strong>No repeatable tables</strong></div>}{tables.map((item) => <article className="repeatable-section-admin" key={item.id}><header><div><strong>{item.label}</strong><span>{item.min_rows || 0} minimum Â· {item.max_rows || "unlimited"} maximum rows</span></div><span>{(item.columns || []).length} columns</span></header><div className="table-scroll compact-builder-table"><table><thead><tr><th>Column</th><th>Type</th><th>Required</th><th>Certificate</th></tr></thead><tbody>{(item.columns || []).map((col: Row) => <tr key={col.id}><td>{col.label}</td><td>{col.column_type}</td><td>{Number(col.is_required) ? "Yes" : "No"}</td><td>{Number(col.appears_on_pdf ?? 1) ? "Yes" : "No"}</td></tr>)}</tbody></table></div></article>)}</div><div className="safe-operation-note"><ShieldCheck size={17} />Existing tables and columns are protected from deletion to preserve historical mappings.</div></Panel></>}

      {section === "publish" && <Panel title="Preview & publish"><div className="publish-summary"><FileBadge size={34} /><div><h3>{selected.name} form v{template?.version || selected.current_version || 1}</h3><p>{fields.length} fields and {tables.length} repeatable tables are active.</p></div><button className="primary-button" type="button" onClick={() => void publish()}><FileBadge size={16} />Publish new version</button></div><div className="safe-operation-note"><ShieldCheck size={17} />Publishing creates a new active form version. Existing inspections, PDFs and historical versions are not overwritten.</div></Panel>}

      {isSuperAdmin && <details className="advanced-settings"><summary><ChevronDown size={16} />Super Admin advanced developer settings</summary><div className="advanced-settings-grid">
        {[["Template family", selected.template_family], ["Layout key", selected.layout_key], ["Source sample", selected.source_sample], ["Schema version", selected.schema_version], ["Certificate template", selected.certificate_template], ["Certificate prefix", selected.certificate_prefix], ["Identifier label", selected.identifier_label], ["Theme color", selected.theme_color]].map(([label, value]) => <div key={String(label)}><span>{label}</span><code>{value || "not set"}</code></div>)}
        <div className="wide-field"><span>System field keys</span><code>{fields.map((item) => item.field_key).join(", ") || "none"}</code></div><div className="wide-field"><span>Repeatable mapping keys</span><code>{tables.map((item) => `${item.section_key}: ${(item.columns || []).map((col: Row) => col.column_key).join(", ")}`).join(" | ") || "none"}</code></div>
      </div><p>Read-only. Changing these values can break renderers, saved inspections, verification and issued certificate archives.</p></details>}
    </div>}
  </>;
}


