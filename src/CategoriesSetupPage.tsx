import { useEffect, useMemo, useState, type FormEvent } from "react";
import { FileBadge, Layers3, Plus, Save, Search, Settings, Trash2 } from "lucide-react";

type ApiResponse<T> = { data: T | null; error: string | null; validation?: Record<string, string> | null };
type ApiRequest = <T>(path: string, options?: RequestInit, csrf?: string) => Promise<ApiResponse<T>>;
type Row = Record<string, any>;

type Props = {
  csrf: string;
  request: ApiRequest;
};

const fieldTypes = ["text", "textarea", "number", "date", "select", "checkbox", "pass_fail", "photo", "signature"];
const columnTypes = ["text", "textarea", "number", "date", "select", "checkbox", "pass_fail"];

const emptyCategory = {
  code: "",
  short_code: "",
  name: "",
  description: "",
  validity_months: "12",
  certificate_template: "",
  template_family: "",
  layout_key: "",
  source_sample: "",
  schema_version: "1",
  certificate_prefix: "",
  identifier_label: "Certificate Number",
  theme_color: "#151515",
  requires_review: true,
  status: "active",
};

const emptyField = {
  field_key: "",
  label: "",
  help_text: "",
  placeholder_text: "",
  field_type: "text",
  is_required: false,
  appears_on_pdf: true,
  editable_after_approval: false,
  pdf_section: "summary",
  repeatable_group: "",
  sort_order: "100",
  options: "",
};

const emptySection = {
  section_key: "",
  label: "",
  help_text: "",
  min_rows: "0",
  max_rows: "",
  pdf_section: "checklist",
  sort_order: "100",
};

const emptyColumn = {
  section_id: "",
  column_key: "",
  label: "",
  column_type: "text",
  is_required: false,
  appears_on_pdf: true,
  editable_after_approval: false,
  placeholder_text: "",
  sort_order: "100",
  options: "",
};

function Panel({ title, action, children }: { title: string; action?: React.ReactNode; children: React.ReactNode }) {
  return <section className="panel"><header className="panel-header"><h2>{title}</h2>{action}</header>{children}</section>;
}

function normalizeOptions(value: string) {
  const options = value.split(",").map((item) => item.trim()).filter(Boolean);
  return options.length ? options : null;
}

function categoryToForm(item: Row) {
  return {
    code: item.code || "",
    short_code: item.short_code || "",
    name: item.name || "",
    description: item.description || "",
    validity_months: String(item.validity_months || 12),
    certificate_template: item.certificate_template || "",
    template_family: item.template_family || "",
    layout_key: item.layout_key || "",
    source_sample: item.source_sample || "",
    schema_version: String(item.schema_version || 1),
    certificate_prefix: item.certificate_prefix || "",
    identifier_label: item.identifier_label || "Certificate Number",
    theme_color: item.theme_color || "#151515",
    requires_review: Number(item.requires_review ?? 1) === 1,
    status: item.status || "active",
  };
}

export function EnhancedCategoriesSetupPage({ csrf, request }: Props) {
  const [categories, setCategories] = useState<Row[]>([]);
  const [selected, setSelected] = useState<Row | null>(null);
  const [template, setTemplate] = useState<Row | null>(null);
  const [fields, setFields] = useState<Row[]>([]);
  const [sections, setSections] = useState<Row[]>([]);
  const [categoryForm, setCategoryForm] = useState(emptyCategory);
  const [newCategory, setNewCategory] = useState(emptyCategory);
  const [fieldForm, setFieldForm] = useState(emptyField);
  const [sectionForm, setSectionForm] = useState(emptySection);
  const [columnForm, setColumnForm] = useState(emptyColumn);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [filter, setFilter] = useState("");

  const filteredCategories = useMemo(() => {
    const term = filter.trim().toLowerCase();
    if (!term) return categories;
    return categories.filter((item) => [item.name, item.code, item.short_code, item.template_family, item.source_sample].some((value) => String(value || "").toLowerCase().includes(term)));
  }, [categories, filter]);

  async function load(selectId?: number) {
    const res = await request<{ categories: Row[] }>("/categories/index.php");
    if (res.error) {
      setError(res.error);
      return;
    }
    const rows = res.data?.categories || [];
    setCategories(rows);
    const next = rows.find((item) => Number(item.id) === Number(selectId || selected?.id)) || rows[0] || null;
    if (next) await selectCategory(next);
  }

  async function selectCategory(item: Row) {
    setSelected(item);
    setCategoryForm(categoryToForm(item));
    setError("");
    const res = await request<{ template: Row | null; fields: Row[]; sections?: Row[] }>(`/categories/fields.php?category_id=${item.id}`);
    if (res.error) {
      setError(res.error);
      return;
    }
    setTemplate(res.data?.template || null);
    setFields(res.data?.fields || []);
    setSections(res.data?.sections || []);
  }

  useEffect(() => { void load(); }, []);

  async function createCategory(event: FormEvent) {
    event.preventDefault();
    setError("");
    setMessage("");
    const res = await request<{ category: Row }>("/categories/index.php", {
      method: "POST",
      body: JSON.stringify({
        ...newCategory,
        validity_months: Number(newCategory.validity_months),
        schema_version: Number(newCategory.schema_version),
      }),
    }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("Category created with starter certificate fields.");
    setNewCategory(emptyCategory);
    await load(Number(res.data?.category?.id));
  }

  async function saveCategory() {
    if (!selected) return;
    setError("");
    setMessage("");
    const res = await request(`/categories/category.php?id=${selected.id}`, {
      method: "PATCH",
      body: JSON.stringify({
        ...categoryForm,
        validity_months: Number(categoryForm.validity_months),
        schema_version: Number(categoryForm.schema_version),
      }),
    }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("Category rules and PDF metadata saved.");
    await load(Number(selected.id));
  }

  async function saveTemplateSettings() {
    if (!selected || !template) return;
    setError("");
    const res = await request<{ template: Row; message: string }>("/categories/template-settings.php", {
      method: "POST",
      body: JSON.stringify({ category_id: Number(selected.id), ...template, minimum_evidence_files: Number(template.minimum_evidence_files || 0) }),
    }, csrf);
    if (res.error) { setError(res.error); return; }
    if (res.data?.template) setTemplate(res.data.template);
    setMessage(res.data?.message || "Certificate authentication rules saved.");
  }
  async function addField(event: FormEvent) {
    event.preventDefault();
    if (!selected) return;
    setError("");
    const res = await request(`/categories/fields.php?category_id=${selected.id}`, {
      method: "POST",
      body: JSON.stringify({
        ...fieldForm,
        sort_order: Number(fieldForm.sort_order),
        options: normalizeOptions(fieldForm.options),
      }),
    }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("Field added to the active certificate form.");
    setFieldForm(emptyField);
    await selectCategory(selected);
  }

  async function deleteField(id: number) {
    setError("");
    const res = await request("/categories/field.php", { method: "POST", body: JSON.stringify({ id, action: "delete" }) }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("Field removed.");
    if (selected) await selectCategory(selected);
  }

  async function addSection(event: FormEvent) {
    event.preventDefault();
    if (!selected) return;
    setError("");
    const res = await request(`/categories/sections.php?category_id=${selected.id}`, {
      method: "POST",
      body: JSON.stringify({
        ...sectionForm,
        min_rows: Number(sectionForm.min_rows || 0),
        max_rows: sectionForm.max_rows ? Number(sectionForm.max_rows) : null,
        sort_order: Number(sectionForm.sort_order),
      }),
    }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("Repeatable section added.");
    setSectionForm(emptySection);
    if (selected) await selectCategory(selected);
  }

  async function deleteSection(id: number) {
    setError("");
    const res = await request("/categories/section.php", { method: "POST", body: JSON.stringify({ id, action: "delete" }) }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("Repeatable section removed.");
    if (selected) await selectCategory(selected);
  }

  async function addColumn(event: FormEvent) {
    event.preventDefault();
    if (!selected || !columnForm.section_id) return;
    setError("");
    const res = await request("/categories/section.php", {
      method: "POST",
      body: JSON.stringify({
        ...columnForm,
        id: Number(columnForm.section_id),
        action: "add_column",
        sort_order: Number(columnForm.sort_order),
        options: normalizeOptions(columnForm.options),
      }),
    }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("Column added to repeatable section.");
    setColumnForm({ ...emptyColumn, section_id: columnForm.section_id });
    await selectCategory(selected);
  }

  async function deleteColumn(sectionId: number, columnId: number) {
    setError("");
    const res = await request("/categories/section.php", { method: "POST", body: JSON.stringify({ id: sectionId, action: "delete_column", column_id: columnId }) }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("Column removed.");
    if (selected) await selectCategory(selected);
  }

  async function publishVersion() {
    if (!selected) return;
    const res = await request("/categories/publish-version.php", { method: "POST", body: JSON.stringify({ category_id: selected.id }) }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("New active form version published.");
    await selectCategory(selected);
  }

  function setCategoryValue(key: string, value: string | boolean) {
    setCategoryForm((current) => ({ ...current, [key]: value }));
  }

  function setNewCategoryValue(key: string, value: string | boolean) {
    setNewCategory((current) => ({ ...current, [key]: value }));
  }

  return <>
    <div className="page-heading">
      <div>
        <p className="eyebrow">DYNAMIC CERTIFICATION SETUP</p>
        <h1>Categories, templates & inspection forms</h1>
        <span>Manage certificate families, PDF-facing metadata, dynamic fields and repeatable inspection tables.</span>
      </div>
    </div>

    {error && <div className="inline-alert form-error">{error}</div>}
    {message && <div className="inline-alert form-success">{message}</div>}

    <div className="setup-layout category-setup-layout">
      <Panel title="Create category">
        <form className="record-form setup-form" onSubmit={createCategory}>
          <label>Code<input value={newCategory.code} onChange={(event) => setNewCategoryValue("code", event.target.value.toUpperCase())} placeholder="WEBSLNG" /></label>
          <label>Short code<input value={newCategory.short_code} onChange={(event) => setNewCategoryValue("short_code", event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, ""))} placeholder="WEBSLNG" /></label>
          <label>Name<input value={newCategory.name} onChange={(event) => setNewCategoryValue("name", event.target.value)} placeholder="Flat Webbing Sling" /></label>
          <label>Validity months<input type="number" value={newCategory.validity_months} onChange={(event) => setNewCategoryValue("validity_months", event.target.value)} /></label>
          <label>Template family<input value={newCategory.template_family} onChange={(event) => setNewCategoryValue("template_family", event.target.value.toLowerCase())} placeholder="lifting_accessory" /></label>
          <label>Layout key<input value={newCategory.layout_key} onChange={(event) => setNewCategoryValue("layout_key", event.target.value)} placeholder="lifting-accessory-v1" /></label>
          <label>Source sample<input value={newCategory.source_sample} onChange={(event) => setNewCategoryValue("source_sample", event.target.value)} placeholder="Converted certificate filename" /></label>
          <label>Theme color<input value={newCategory.theme_color} onChange={(event) => setNewCategoryValue("theme_color", event.target.value)} /></label>
          <button className="primary-button"><Plus size={16} />Create</button>
        </form>
      </Panel>

      <Panel title="Category library" action={<label className="builder-search"><Search size={15} /><input value={filter} onChange={(event) => setFilter(event.target.value)} placeholder="Search category" /></label>}>
        <div className="category-list category-library-list">
          {filteredCategories.map((item) => <button key={item.id} className={selected?.id === item.id ? "selected" : ""} onClick={() => void selectCategory(item)}>
            <span style={{ background: item.theme_color || "#151515" }}>{item.short_code || item.code}</span>
            <strong>{item.name}</strong>
            <small>{item.field_count || 0} fields / {item.section_count || 0} tables / {item.validity_months} months</small>
          </button>)}
        </div>
      </Panel>
    </div>

    {selected && <div className="category-builder-shell">
      <aside className="category-builder-sidebar">
        <strong>{selected.name}</strong>
        <span>{selected.source_sample || selected.template_family || "Custom JUVA category"}</span>
        <button onClick={() => void saveCategory()}><Save size={15} />Save metadata</button>
        <button onClick={() => void publishVersion()}><FileBadge size={15} />Publish v{Number(template?.version || selected.current_version || 1) + 1}</button>
        <div className="category-builder-stat"><b>v{template?.version || selected.current_version || 1}</b><span>Active form</span></div>
        <div className="category-builder-stat"><b>{fields.length}</b><span>Fields</span></div>
        <div className="category-builder-stat"><b>{sections.length}</b><span>Repeatable tables</span></div>
      </aside>

      <div className="category-builder-main">
        <Panel title="Certificate family & PDF metadata">
          <div className="record-form category-metadata-form">
            <label>Code<input value={categoryForm.code} onChange={(event) => setCategoryValue("code", event.target.value.toUpperCase())} /></label>
            <label>Short code<input value={categoryForm.short_code} onChange={(event) => setCategoryValue("short_code", event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, ""))} /></label>
            <label>Name<input value={categoryForm.name} onChange={(event) => setCategoryValue("name", event.target.value)} /></label>
            <label>Status<select value={categoryForm.status} onChange={(event) => setCategoryValue("status", event.target.value)}><option value="active">Active</option><option value="draft">Draft</option><option value="inactive">Inactive</option></select></label>
            <label>Validity months<input type="number" value={categoryForm.validity_months} onChange={(event) => setCategoryValue("validity_months", event.target.value)} /></label>
            <label>Certificate template<input value={categoryForm.certificate_template} onChange={(event) => setCategoryValue("certificate_template", event.target.value)} /></label>
            <label>Template family<input value={categoryForm.template_family} onChange={(event) => setCategoryValue("template_family", event.target.value.toLowerCase())} /></label>
            <label>Layout key<input value={categoryForm.layout_key} onChange={(event) => setCategoryValue("layout_key", event.target.value)} /></label>
            <label>Source sample<input value={categoryForm.source_sample} onChange={(event) => setCategoryValue("source_sample", event.target.value)} /></label>
            <label>Identifier label<input value={categoryForm.identifier_label} onChange={(event) => setCategoryValue("identifier_label", event.target.value)} /></label>
            <label>Certificate prefix<input value={categoryForm.certificate_prefix} onChange={(event) => setCategoryValue("certificate_prefix", event.target.value.toUpperCase())} /></label>
            <label>Theme color<div className="color-input-row"><input value={categoryForm.theme_color} onChange={(event) => setCategoryValue("theme_color", event.target.value)} /><span style={{ background: categoryForm.theme_color }} /></div></label>
            <label className="wide-field">Description<textarea value={categoryForm.description} onChange={(event) => setCategoryValue("description", event.target.value)} /></label>
            <label className="check-label builder-check"><input type="checkbox" checked={categoryForm.requires_review} onChange={(event) => setCategoryValue("requires_review", event.target.checked)} /> Requires reviewer approval</label>
          </div>
        </Panel>

        <Panel title="Evidence, signatures and stamp rules" action={<button className="secondary-button" type="button" onClick={() => void saveTemplateSettings()}><Save size={16} />Save rules</button>}>
          <div className="record-form category-metadata-form">
            <label className="check-label builder-check"><input type="checkbox" checked={Number(template?.show_inspector_signature ?? 1) === 1} onChange={(event) => setTemplate({ ...(template || {}), show_inspector_signature: event.target.checked ? 1 : 0 })} /> Show inspector signature</label>
            <label className="check-label builder-check"><input type="checkbox" checked={Number(template?.show_authenticator_signature ?? 1) === 1} onChange={(event) => setTemplate({ ...(template || {}), show_authenticator_signature: event.target.checked ? 1 : 0 })} /> Show authenticator signature</label>
            <label className="check-label builder-check"><input type="checkbox" checked={Number(template?.show_company_stamp ?? 0) === 1} onChange={(event) => setTemplate({ ...(template || {}), show_company_stamp: event.target.checked ? 1 : 0 })} /> Show company stamp</label>
            <label className="check-label builder-check"><input type="checkbox" checked={Number(template?.requires_evidence ?? 0) === 1} onChange={(event) => setTemplate({ ...(template || {}), requires_evidence: event.target.checked ? 1 : 0 })} /> Require evidence before issue</label>
            <label>Minimum evidence files<input type="number" min="0" max="20" value={template?.minimum_evidence_files ?? 0} onChange={(event) => setTemplate({ ...(template || {}), minimum_evidence_files: event.target.value })} /></label>
            <label className="wide-field">Allowed evidence MIME types<input value={template?.allowed_evidence_types || "image/jpeg,image/png,image/webp,application/pdf"} onChange={(event) => setTemplate({ ...(template || {}), allowed_evidence_types: event.target.value })} /></label>
            <label className="check-label builder-check"><input type="checkbox" checked={Number(template?.requires_inspector_signature ?? 0) === 1} onChange={(event) => setTemplate({ ...(template || {}), requires_inspector_signature: event.target.checked ? 1 : 0 })} /> Require inspector signature</label>
            <label className="check-label builder-check"><input type="checkbox" checked={Number(template?.requires_authenticator_signature ?? 0) === 1} onChange={(event) => setTemplate({ ...(template || {}), requires_authenticator_signature: event.target.checked ? 1 : 0 })} /> Require authenticator signature</label>
            <label className="check-label builder-check"><input type="checkbox" checked={Number(template?.requires_company_stamp ?? 0) === 1} onChange={(event) => setTemplate({ ...(template || {}), requires_company_stamp: event.target.checked ? 1 : 0 })} /> Require active company stamp</label>
          </div>
        </Panel>
        <div className="builder-two-column">
          <Panel title="Add field">
            <form className="record-form category-field-form" onSubmit={addField}>
              <label>Field key<input value={fieldForm.field_key} onChange={(event) => setFieldForm({ ...fieldForm, field_key: event.target.value.toLowerCase().replace(/[^a-z0-9_]/g, "_") })} placeholder="safe_working_load" /></label>
              <label>Label<input value={fieldForm.label} onChange={(event) => setFieldForm({ ...fieldForm, label: event.target.value })} /></label>
              <label>Type<select value={fieldForm.field_type} onChange={(event) => setFieldForm({ ...fieldForm, field_type: event.target.value })}>{fieldTypes.map((type) => <option key={type} value={type}>{type.replace("_", " / ")}</option>)}</select></label>
              <label>PDF section<input value={fieldForm.pdf_section} onChange={(event) => setFieldForm({ ...fieldForm, pdf_section: event.target.value })} /></label>
              <label>Placeholder<input value={fieldForm.placeholder_text} onChange={(event) => setFieldForm({ ...fieldForm, placeholder_text: event.target.value })} /></label>
              <label>Options<input value={fieldForm.options} onChange={(event) => setFieldForm({ ...fieldForm, options: event.target.value })} placeholder="Pass, Fail, N/A" /></label>
              <label>Sort<input type="number" value={fieldForm.sort_order} onChange={(event) => setFieldForm({ ...fieldForm, sort_order: event.target.value })} /></label>
              <label className="wide-field">Help text<input value={fieldForm.help_text} onChange={(event) => setFieldForm({ ...fieldForm, help_text: event.target.value })} /></label>
              <label className="check-label builder-check"><input type="checkbox" checked={fieldForm.is_required} onChange={(event) => setFieldForm({ ...fieldForm, is_required: event.target.checked })} /> Required</label>
              <label className="check-label builder-check"><input type="checkbox" checked={fieldForm.appears_on_pdf} onChange={(event) => setFieldForm({ ...fieldForm, appears_on_pdf: event.target.checked })} /> Appears on PDF</label>
              <button className="primary-button"><Plus size={16} />Add field</button>
            </form>
          </Panel>

          <Panel title="Field library">
            <div className="table-scroll compact-builder-table">
              <table>
                <thead><tr><th>Sort</th><th>Key</th><th>Label</th><th>PDF</th><th /></tr></thead>
                <tbody>{fields.map((field) => <tr key={field.id}>
                  <td>{field.sort_order}</td>
                  <td className="mono">{field.field_key}</td>
                  <td>{field.label}<small>{field.help_text || field.field_type}</small></td>
                  <td>{Number(field.appears_on_pdf ?? 1) ? field.pdf_section || "yes" : "hidden"}</td>
                  <td><button className="link-button danger-link" onClick={() => void deleteField(Number(field.id))}><Trash2 size={14} />Remove</button></td>
                </tr>)}</tbody>
              </table>
            </div>
          </Panel>
        </div>

        <div className="builder-two-column">
          <Panel title="Add repeatable section">
            <form className="record-form category-section-form" onSubmit={addSection}>
              <label>Section key<input value={sectionForm.section_key} onChange={(event) => setSectionForm({ ...sectionForm, section_key: event.target.value.toLowerCase().replace(/[^a-z0-9_]/g, "_") })} placeholder="visual_checklist" /></label>
              <label>Label<input value={sectionForm.label} onChange={(event) => setSectionForm({ ...sectionForm, label: event.target.value })} /></label>
              <label>PDF section<input value={sectionForm.pdf_section} onChange={(event) => setSectionForm({ ...sectionForm, pdf_section: event.target.value })} /></label>
              <label>Min rows<input type="number" value={sectionForm.min_rows} onChange={(event) => setSectionForm({ ...sectionForm, min_rows: event.target.value })} /></label>
              <label>Max rows<input type="number" value={sectionForm.max_rows} onChange={(event) => setSectionForm({ ...sectionForm, max_rows: event.target.value })} /></label>
              <label>Sort<input type="number" value={sectionForm.sort_order} onChange={(event) => setSectionForm({ ...sectionForm, sort_order: event.target.value })} /></label>
              <label className="wide-field">Help text<input value={sectionForm.help_text} onChange={(event) => setSectionForm({ ...sectionForm, help_text: event.target.value })} /></label>
              <button className="primary-button"><Layers3 size={16} />Add section</button>
            </form>
          </Panel>

          <Panel title="Add section column">
            <form className="record-form category-section-form" onSubmit={addColumn}>
              <label>Section<select value={columnForm.section_id} onChange={(event) => setColumnForm({ ...columnForm, section_id: event.target.value })}><option value="">Select section</option>{sections.map((section) => <option key={section.id} value={section.id}>{section.label}</option>)}</select></label>
              <label>Column key<input value={columnForm.column_key} onChange={(event) => setColumnForm({ ...columnForm, column_key: event.target.value.toLowerCase().replace(/[^a-z0-9_]/g, "_") })} /></label>
              <label>Label<input value={columnForm.label} onChange={(event) => setColumnForm({ ...columnForm, label: event.target.value })} /></label>
              <label>Type<select value={columnForm.column_type} onChange={(event) => setColumnForm({ ...columnForm, column_type: event.target.value })}>{columnTypes.map((type) => <option key={type} value={type}>{type.replace("_", " / ")}</option>)}</select></label>
              <label>Options<input value={columnForm.options} onChange={(event) => setColumnForm({ ...columnForm, options: event.target.value })} placeholder="Pass, Fail, N/A" /></label>
              <label>Sort<input type="number" value={columnForm.sort_order} onChange={(event) => setColumnForm({ ...columnForm, sort_order: event.target.value })} /></label>
              <label className="check-label builder-check"><input type="checkbox" checked={columnForm.is_required} onChange={(event) => setColumnForm({ ...columnForm, is_required: event.target.checked })} /> Required</label>
              <label className="check-label builder-check"><input type="checkbox" checked={columnForm.appears_on_pdf} onChange={(event) => setColumnForm({ ...columnForm, appears_on_pdf: event.target.checked })} /> Appears on PDF</label>
              <button className="primary-button"><Plus size={16} />Add column</button>
            </form>
          </Panel>
        </div>

        <Panel title="Repeatable section builder" action={<button className="secondary-button" onClick={() => selected && void selectCategory(selected)}><Search size={16} />Refresh</button>}>
          <div className="repeatable-section-list">
            {sections.length === 0 && <div className="empty-state compact-empty"><Settings size={26} /><strong>No repeatable sections</strong><span>Add a table for checklist-style certificates such as CCU, shackles, or NDT findings.</span></div>}
            {sections.map((section) => <div key={section.id} className="repeatable-section-admin">
              <header>
                <div><strong>{section.label}</strong><span className="mono">{section.section_key} / {section.pdf_section || "pdf"}</span></div>
                <button className="link-button danger-link" onClick={() => void deleteSection(Number(section.id))}><Trash2 size={14} />Remove section</button>
              </header>
              <div className="table-scroll compact-builder-table">
                <table>
                  <thead><tr><th>Sort</th><th>Key</th><th>Label</th><th>Type</th><th>PDF</th><th /></tr></thead>
                  <tbody>{(section.columns || []).map((column: Row) => <tr key={column.id}>
                    <td>{column.sort_order}</td>
                    <td className="mono">{column.column_key}</td>
                    <td>{column.label}</td>
                    <td>{column.column_type}{Number(column.is_required) ? " / required" : ""}</td>
                    <td>{Number(column.appears_on_pdf ?? 1) ? "yes" : "hidden"}</td>
                    <td><button className="link-button danger-link" onClick={() => void deleteColumn(Number(section.id), Number(column.id))}><Trash2 size={14} />Remove</button></td>
                  </tr>)}</tbody>
                </table>
              </div>
            </div>)}
          </div>
        </Panel>
      </div>
    </div>}
  </>;
}
