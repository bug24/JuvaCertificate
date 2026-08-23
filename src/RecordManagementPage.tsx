import { useEffect, useState, type FormEvent } from "react";
import { Archive, ChevronRight, Plus, RotateCcw, Search, Trash2, X } from "lucide-react";

type Row = Record<string, any>;
type ApiResponse<T> = { data: T | null; error: string | null; validation?: Record<string, string> | null };
type ApiRequest = <T>(path: string, options?: RequestInit, csrf?: string) => Promise<ApiResponse<T>>;
type Props = { page: "clients" | "equipment"; csrf: string; request: ApiRequest; canManage: boolean };

const clientFields = ["registration_code", "short_code", "name", "contact_person", "phone", "email", "address", "status"];
const equipmentFields = ["client_id", "asset_code", "name", "manufacturer", "model", "serial_number", "safe_working_load", "location", "manufacture_date_value", "reference_standard", "status"];
function PartialDateInput({ value, onChange }: { value: string; onChange: (value: string) => void }) {
  const detectedPrecision = /^\d{4}$/.test(value) ? "year" : /^\d{4}-\d{2}$/.test(value) ? "month" : "day";
  const [precision, setPrecision] = useState(detectedPrecision);
  useEffect(() => {
    if (/^\d{4}(-\d{2}(-\d{2})?)?$/.test(value)) setPrecision(detectedPrecision);
  }, [value, detectedPrecision]);
  return <div className="partial-date-control">
    <span>Date precision</span>
    <select value={precision} onChange={(event) => { setPrecision(event.target.value); onChange(""); }}>
      <option value="day">Full date</option>
      <option value="month">Month + year</option>
      <option value="year">Year only</option>
    </select>
    {precision === "day" && <input type="date" value={value} onChange={(event) => onChange(event.target.value)} />}
    {precision === "month" && <input type="month" value={value} onChange={(event) => onChange(event.target.value)} />}
    {precision === "year" && <input type="text" inputMode="numeric" pattern="[0-9]{4}" maxLength={4} placeholder="YYYY" value={value} onChange={(event) => onChange(event.target.value.replace(/\D/g, "").slice(0, 4))} />}
  </div>;
}

const labels: Record<string, string> = {
  registration_code: "Registration code", short_code: "Short code", name: "Name", contact_person: "Contact person",
  phone: "Phone", email: "Email", address: "Address", status: "Status", client_id: "Client", asset_code: "Asset code",
  manufacturer: "Manufacturer", model: "Model", serial_number: "Serial number", safe_working_load: "SWL / capacity",
  location: "Location", manufacture_date: "Manufacture date", manufacture_date_value: "Manufacture date", reference_standard: "Reference standard",
};

export function RecordManagementPage({ page, csrf, request, canManage }: Props) {
  const isClient = page === "clients";
  const title = isClient ? "Client management" : "Equipment register";
  const endpoint = isClient ? "/clients" : "/equipment";
  const fields = isClient ? clientFields : equipmentFields;
  const [rows, setRows] = useState<Row[]>([]);
  const [clients, setClients] = useState<Row[]>([]);
  const [form, setForm] = useState<Row>({});
  const [selected, setSelected] = useState<Row | null>(null);
  const [dependencies, setDependencies] = useState<Row>({});
  const [editing, setEditing] = useState(false);
  const [validation, setValidation] = useState<Record<string, string>>({});
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");

  async function load() {
    const params = search ? `?search=${encodeURIComponent(search)}` : "";
    const result = await request<any>(`${endpoint}/index.php${params}`);
    if (result.data) setRows(result.data[isClient ? "clients" : "equipment"] || []);
    if (result.error) setError(result.error);
    if (!isClient) {
      const clientResult = await request<any>("/clients/index.php");
      if (clientResult.data) setClients(clientResult.data.clients || []);
    }
  }
  useEffect(() => { void load(); }, [page]);

  async function open(row: Row) {
    const result = await request<any>(`${endpoint}/${isClient ? "client" : "equipment"}.php?id=${row.id}`);
    if (result.error || !result.data) { setError(result.error || "Unable to open record."); return; }
    const record = result.data[isClient ? "client" : "equipment"];
    setSelected(record); setForm(record); setDependencies(result.data.dependencies || {}); setEditing(false); setValidation({});
  }
  function close() { setSelected(null); setEditing(false); setValidation({}); }
  function value(key: string) { return String(form[key] ?? ""); }
  function set(key: string, value: string) { setForm(current => ({ ...current, [key]: value })); }

  async function save(event: FormEvent) {
    event.preventDefault(); setError(""); setMessage(""); setValidation({});
    const body = { ...form };
    if (!isClient) body.client_id = Number(body.client_id);
    const path = selected ? `${endpoint}/${isClient ? "client" : "equipment"}.php?id=${selected.id}` : `${endpoint}/index.php`;
    const result = await request<any>(path, { method: selected ? "PATCH" : "POST", body: JSON.stringify(body) }, csrf);
    if (result.error) { setError(result.error); setValidation(result.validation || {}); return; }
    setMessage(`${title} record ${selected ? "updated" : "created"} successfully.`);
    if (selected) await open(result.data[isClient ? "client" : "equipment"]); else setForm({});
    await load();
  }

  async function remove() {
    if (!selected || !window.confirm(`Delete or archive ${selected.name}? Historical records will be preserved.`)) return;
    const result = await request<any>(`${endpoint}/${isClient ? "client" : "equipment"}.php?id=${selected.id}`, { method: "DELETE" }, csrf);
    if (result.error) { setError(result.error); return; }
    setMessage(result.data?.message || "Record updated."); close(); await load();
  }
  async function changeStatus(next: string) {
    if (!selected) return;
    const result = await request<any>(`${endpoint}/${isClient ? "client" : "equipment"}.php?id=${selected.id}`, { method: "PATCH", body: JSON.stringify({ status: next }) }, csrf);
    if (result.error) { setError(result.error); return; }
    setMessage(`Record status changed to ${next.replaceAll("_", " ")}.`); await open(result.data[isClient ? "client" : "equipment"]); await load();
  }

  function input(key: string) {
    if (key === "client_id") return <select value={value(key)} onChange={e => set(key, e.target.value)} disabled={!!selected && Number(dependencies.inspections) > 0}><option value="">Select client</option>{clients.map(client => <option key={client.id} value={client.id}>{client.name}</option>)}</select>;
    if (key === "status") {
      const options = isClient ? [["active", "Active"], ["review", "Review"], ["inactive", "Inactive"]] : [["active", "Active"], ["out_of_service", "Out of service"], ["retired", "Retired"]];
      return <select value={value(key) || "active"} onChange={e => set(key, e.target.value)}>{options.map(([id, text]) => <option key={id} value={id}>{text}</option>)}</select>;
    }
    if (key === "address") return <textarea value={value(key)} onChange={e => set(key, e.target.value)} />;
    if (key === "manufacture_date_value") return <PartialDateInput value={value(key)} onChange={(next) => set(key, next)} />;
    return <input type={key === "email" ? "email" : "text"} value={value(key)} onChange={e => set(key, key === "short_code" ? e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "") : e.target.value)} />;
  }

  const columns = isClient ? ["registration_code", "short_code", "name", "contact_person", "phone", "status", "equipment_count"] : ["asset_code", "name", "client_name", "serial_number", "safe_working_load", "next_due_date", "status"];
  return <><div className="page-heading"><div><p className="eyebrow">CORE RECORDS</p><h1>{title}</h1><span>Create, edit, archive and safely remove operational records.</span></div></div>
    {error && <div className="inline-alert form-error">{error}</div>}{message && <div className="inline-alert form-success">{message}</div>}
    {canManage && <section className="panel"><header className="panel-header"><h2>Create {isClient ? "client" : "equipment"}</h2></header><form className="record-form management-form" onSubmit={save}>{fields.filter(field => field !== "status").map(field => <label key={field} className={field === "address" ? "wide-field" : ""}>{labels[field]}{input(field)}{validation[field] && <small className="field-error">{validation[field]}</small>}</label>)}<button className="primary-button"><Plus size={16} />Save record</button></form></section>}
    <section className="panel"><header className="panel-header"><h2>{title}</h2><div className="record-search"><Search size={16} /><input value={search} onChange={e => setSearch(e.target.value)} onKeyDown={e => { if (e.key === "Enter") void load(); }} placeholder={`Search ${isClient ? "clients" : "equipment"}`} /><button className="secondary-button" onClick={() => void load()}>Search</button></div></header><div className="table-scroll"><table><thead><tr>{columns.map(column => <th key={column}>{labels[column] || column.replaceAll("_", " ")}</th>)}<th>Actions</th></tr></thead><tbody>{rows.map(row => <tr key={row.id} className="clickable-row" onClick={() => void open(row)}>{columns.map(column => <td key={column}>{String(row[column] ?? "-")}</td>)}<td><button className="icon-button" title="Open record" onClick={event => { event.stopPropagation(); void open(row); }}><ChevronRight size={17} /></button></td></tr>)}</tbody></table></div></section>
    {selected && <div className="drawer-backdrop" onClick={close}><aside className="record-drawer" onClick={event => event.stopPropagation()}><header><div><span className="eyebrow">{isClient ? "CLIENT RECORD" : "EQUIPMENT RECORD"}</span><h2>{selected.name}</h2></div><button className="icon-button" onClick={close} title="Close"><X size={20} /></button></header><div className="dependency-summary">{Object.entries(dependencies).map(([key, count]) => <span key={key}>{key.replaceAll("_", " ")}: <b>{String(count)}</b></span>)}</div>{editing ? <form className="drawer-form" onSubmit={save}>{fields.map(field => <label key={field}>{labels[field]}{input(field)}{validation[field] && <small className="field-error">{validation[field]}</small>}</label>)}<div className="drawer-actions"><button type="button" className="secondary-button" onClick={() => { setForm(selected); setEditing(false); }}>Cancel</button><button className="primary-button">Save changes</button></div></form> : <dl className="record-detail-list">{fields.map(field => <div key={field}><dt>{labels[field]}</dt><dd>{value(field) || "-"}</dd></div>)}</dl>}<footer>{canManage && !editing && <><button className="primary-button" onClick={() => setEditing(true)}>Edit record</button><button className="secondary-button" onClick={() => void changeStatus(isClient ? selected.status === "inactive" ? "active" : "inactive" : selected.status === "retired" ? "active" : "retired")}>{selected.status === "inactive" || selected.status === "retired" ? <RotateCcw size={16} /> : <Archive size={16} />}{selected.status === "inactive" || selected.status === "retired" ? "Reactivate" : isClient ? "Deactivate" : "Retire"}</button><button className="danger-button" onClick={() => void remove()}><Trash2 size={16} />Delete</button></>}</footer></aside></div>}
  </>;
}
