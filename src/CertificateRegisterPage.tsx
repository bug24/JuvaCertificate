import { useEffect, useMemo, useRef, useState, type ReactNode } from "react";
import { CopyPlus, Download, ExternalLink, FileBadge, Mail, Pencil, Printer, QrCode, Search, X } from "lucide-react";

type RoleLike = { slug: string; permissions: string[] };
type UserLike = { role: RoleLike };
type ApiResponse<T> = { data: T | null; error: string | null; validation?: unknown };
type ApiRequest = <T>(path: string, options?: RequestInit, csrf?: string) => Promise<ApiResponse<T>>;
type Row = Record<string, any>;
type FilterState = { q: string; client_id: string; category_id: string; status: string; inspection_from: string; inspection_to: string };
type Props = { csrf: string; user: UserLike; request: ApiRequest; apiBase: string; onOpenInspection?: (id: number) => void };

const emptyFilters: FilterState = { q: "", client_id: "", category_id: "", status: "", inspection_from: "", inspection_to: "" };
const can = (u: UserLike, permission: string) => u.role.permissions.includes("*") || u.role.permissions.includes(permission);
function tone(status: string) { const value = status.toLowerCase(); if (["valid", "issued", "approved", "active"].includes(value)) return "valid"; if (["submitted", "pending"].includes(value)) return "warning"; if (["expired", "revoked"].includes(value)) return "danger"; return "neutral"; }
function StatusBadge({ status }: { status: string }) { return <span className={`status-badge status-${tone(status)}`}>{status}</span>; }
function Panel({ title, action, children }: { title: string; action?: ReactNode; children: ReactNode }) { return <section className="panel"><header className="panel-header"><h2>{title}</h2>{action}</header>{children}</section>; }

export function EnhancedCertificateRegisterPage({ csrf, user, request, apiBase, onOpenInspection }: Props) {
  const [certs, setCerts] = useState<Row[]>([]);
  const [inspections, setInspections] = useState<Row[]>([]);
  const [detailSelection, setDetailSelection] = useState<Row | null>(null);
  const [detail, setDetail] = useState<any>(null);
  const [notifications, setNotifications] = useState<Row[]>([]);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [filters, setFilters] = useState<FilterState>(emptyFilters);
  const [applied, setApplied] = useState<FilterState>(emptyFilters);
  const [filterOptions, setFilterOptions] = useState<{ clients: Row[]; categories: Row[] }>({ clients: [], categories: [] });
  const [page, setPage] = useState(1);
  const [pages, setPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [selectedRows, setSelectedRows] = useState<Map<number, Row>>(new Map());
  const [batchBusy, setBatchBusy] = useState(false);
  const selectAllRef = useRef<HTMLInputElement>(null);
  const certificatesSectionRef = useRef<HTMLElement>(null);
  const archiveSectionRef = useRef<HTMLElement>(null);
  const printSectionRef = useRef<HTMLFormElement>(null);
  const [activeSection, setActiveSection] = useState<"certificates" | "archive" | "print">("certificates");
  const canIssue = can(user, "certificates.issue") || ["super-admin", "operations-admin"].includes(user.role.slug);
  const canRenew = can(user, "inspections.create") || can(user, "inspections.edit");

  async function load() {
    const params = new URLSearchParams({ page: String(page), per_page: "25" });
    Object.entries(applied).forEach(([key, value]) => { if (value) params.set(key, value); });
    const [certificateResult, inspectionResult] = await Promise.all([
      request<any>(`/certificates/index.php?${params}`),
      request<any>("/inspections/index.php"),
    ]);
    if (certificateResult.data) {
      setCerts(certificateResult.data.certificates || []);
      setPages(certificateResult.data.pagination?.pages || 1);
      setTotal(certificateResult.data.pagination?.total || 0);
      setFilterOptions(certificateResult.data.filter_options || { clients: [], categories: [] });
    }
    if (inspectionResult.data) setInspections((inspectionResult.data.inspections || []).filter((row: Row) => ["approved", "issued"].includes(String(row.status))));
    if (certificateResult.error) setError(certificateResult.error);
  }
  useEffect(() => { void load(); }, [applied, page]);

  const visibleIds = useMemo(() => certs.map((certificate) => Number(certificate.id)), [certs]);
  const visibleSelected = visibleIds.filter((id) => selectedRows.has(id)).length;
  const allVisibleSelected = visibleIds.length > 0 && visibleSelected === visibleIds.length;
  useEffect(() => { if (selectAllRef.current) selectAllRef.current.indeterminate = visibleSelected > 0 && !allVisibleSelected; }, [visibleSelected, allVisibleSelected]);
  const orderedSelectedIds = useMemo(() => Array.from(selectedRows.values()).sort((a, b) => {
    const issued = String(b.issued_at || "").localeCompare(String(a.issued_at || ""));
    return issued || Number(b.id) - Number(a.id);
  }).map((row) => Number(row.id)), [selectedRows]);

  function toggleCertificate(certificate: Row, checked: boolean) {
    setSelectedRows((current) => { const next = new Map(current); if (checked) next.set(Number(certificate.id), certificate); else next.delete(Number(certificate.id)); return next; });
  }
  function toggleVisible(checked: boolean) {
    setSelectedRows((current) => { const next = new Map(current); certs.forEach((certificate) => { const id = Number(certificate.id); if (checked) next.set(id, certificate); else next.delete(id); }); return next; });
  }
  function applyFilters(event: React.FormEvent) { event.preventDefault(); setPage(1); setApplied({ ...filters }); }
  function resetFilters() { setFilters(emptyFilters); setApplied(emptyFilters); setPage(1); }

  async function runBatch(disposition: "attachment" | "inline") {
    if (!orderedSelectedIds.length || batchBusy) return;
    setError(""); setMessage(""); setBatchBusy(true);
    const printWindow = disposition === "inline" ? window.open("", "_blank") : null;
    if (printWindow) { printWindow.opener = null; printWindow.document.title = "Preparing JUVA certificates"; printWindow.document.body.textContent = "Preparing combined certificate PDF..."; }
    try {
      const response = await fetch(`${apiBase}/certificates/batch.php`, {
        method: "POST", credentials: "include",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf },
        body: JSON.stringify({ certificate_ids: orderedSelectedIds, disposition, filters: applied }),
      });
      if (!response.ok) {
        const payload = await response.json().catch(() => ({ error: "Unable to prepare the combined PDF." }));
        throw new Error(payload.error || "Unable to prepare the combined PDF.");
      }
      const blob = await response.blob();
      const signature = String.fromCharCode(...new Uint8Array(await blob.slice(0, 5).arrayBuffer()));
      if (signature !== "%PDF-") throw new Error("The batch endpoint did not return a valid PDF.");
      const objectUrl = URL.createObjectURL(blob);
      if (disposition === "inline") {
        if (!printWindow) throw new Error("The browser blocked the print preview window. Allow pop-ups and try again.");
        printWindow.location.href = objectUrl;
      } else {
        const anchor = document.createElement("a"); anchor.href = objectUrl; anchor.download = `JUVA-certificates-${new Date().toISOString().slice(0, 10)}.pdf`; anchor.click();
      }
      window.setTimeout(() => URL.revokeObjectURL(objectUrl), 300000);
      setMessage(`${orderedSelectedIds.length} certificates prepared in one combined PDF.`);
    } catch (caught) {
      if (printWindow) printWindow.close();
      setError(caught instanceof Error ? caught.message : "Unable to prepare the combined PDF.");
    } finally { setBatchBusy(false); }
  }

  async function openDetail(certificate: Row) { setDetailSelection(certificate); const [record, notices] = await Promise.all([request<any>(`/certificates/detail.php?id=${certificate.id}`), request<any>(`/certificates/notifications.php?certificate_id=${certificate.id}`)]); if (record.data) setDetail(record.data); else setError(record.error || "Unable to load certificate details."); setNotifications(notices.data?.notifications || []); }
  async function openPdf(certificate: Row) { setError(""); const url = String(certificate.pdf_url || certificate.pdf_path || ""); if (!url) { setError("PDF not available. Create a revision or view the source inspection to repair this certificate."); return; } try { const response = await fetch(url, { credentials: "include" }); if (!response.ok) throw new Error(); const blob = await response.blob(); const signature = String.fromCharCode(...new Uint8Array(await blob.slice(0, 5).arrayBuffer())); if (signature !== "%PDF-") throw new Error(); const objectUrl = URL.createObjectURL(blob); window.open(objectUrl, "_blank", "noopener,noreferrer"); window.setTimeout(() => URL.revokeObjectURL(objectUrl), 60000); } catch { setError("PDF could not be opened. The archived file is missing or the server returned an invalid response. Use Create revision to regenerate it."); } }
  async function repair(certificate: Row) { if (!window.confirm(`Mark ${certificate.certificate_number} invalid and reopen its inspection for repair?`)) return; const result = await request<any>("/certificates/repair.php", { method: "POST", body: JSON.stringify({ id: certificate.id, reason: "Incomplete issuance requires evidence repair." }) }, csrf); if (result.error) { setError(result.error); return; } setMessage(result.data?.message || "Certificate opened for repair."); onOpenInspection?.(Number(result.data?.inspection_id || certificate.inspection_id)); await load(); }
  async function generate(id: number) { setError(""); const ready = await request<any>(`/certificates/readiness.php?inspection_id=${id}`); if (ready.data && !ready.data.ready) { const labels = [...(ready.data.missing_fields || []).map((item: Row) => item.label), ...(ready.data.missing_sections || []).map((item: Row) => item.label)]; setError(`Certificate is not ready. Complete: ${labels.join(", ") || Object.values(ready.data.validation_errors || {}).join(" ")}`); return; } const result = await request<Row>("/certificates/generate.php", { method: "POST", body: JSON.stringify({ inspection_id: id }) }, csrf); if (result.error) { setError(result.error); return; } setMessage(result.data?.unchanged ? `Opening existing certificate: ${result.data.certificate_number}` : `Certificate issued: ${result.data?.certificate_number}`); if (result.data?.pdf_url) await openPdf({ ...result.data, pdf_path: result.data.pdf_url }); await load(); }
  async function renew(certificate: Row) { if (!window.confirm(`Create a renewal draft from ${certificate.certificate_number}?`)) return; const result = await request<any>("/inspections/clone.php", { method: "POST", body: JSON.stringify({ inspection_id: Number(certificate.inspection_id) }) }, csrf); if (result.error || !result.data?.inspection) { setError(result.error || "Unable to clone certificate."); return; } setMessage(`Draft cloned successfully from certificate ${certificate.certificate_number}.`); onOpenInspection?.(Number(result.data.inspection.id)); }
  async function revoke(id: number) { const reason = window.prompt("Revocation reason"); if (!reason) return; const result = await request("/certificates/revoke.php", { method: "POST", body: JSON.stringify({ id, reason }) }, csrf); if (result.error) { setError(result.error); return; } setMessage("Certificate revoked."); await load(); }
  async function createRevision(certificate: Row) { const reason = window.prompt("Reason for creating a new certificate revision"); if (!reason) return; const result = await request<Row>("/certificates/create-revision.php", { method: "POST", body: JSON.stringify({ id: certificate.id, reason }) }, csrf); if (result.error) { setError(result.error); return; } setMessage(`Revision v${result.data?.revision} created for ${certificate.certificate_number}.`); await load(); }
  async function resend(notificationId: number) { const result = await request<any>("/certificates/resend-notification.php", { method: "POST", body: JSON.stringify({ notification_id: notificationId }) }, csrf); if (result.error) { setError(result.error); return; } setMessage(result.data?.sent ? "Certificate email sent." : `Email remains queued: ${result.data?.error || "delivery failed"}`); if (detailSelection) await openDetail(detailSelection); }
  function primaryActions(certificate: Row) { return <div className="archive-primary-actions" onClick={(event) => event.stopPropagation()}><button className="compact-action" onClick={() => void openPdf(certificate)}><FileBadge size={14} /><span className="action-label">Open</span></button><a className="compact-action" href={certificate.verification_url} target="_blank" rel="noreferrer"><ExternalLink size={14} /><span className="action-label">Verify</span></a></div>; }
  function drawerActions(certificate: Row) { return <div className="certificate-drawer-actions"><button className="secondary-button" onClick={() => void openPdf(certificate)}><FileBadge size={14} />Open Certificate</button><a className="secondary-button" href={certificate.verification_url} target="_blank" rel="noreferrer"><ExternalLink size={14} />Verification page</a>{(certificate.qr_url || certificate.barcode_path) && <a className="secondary-button" href={certificate.qr_url || certificate.barcode_path} target="_blank" rel="noreferrer"><QrCode size={14} />Open QR</a>}<button className="secondary-button" onClick={() => onOpenInspection?.(Number(certificate.inspection_id))}><Pencil size={14} />View inspection</button>{canRenew && <button className="secondary-button" onClick={() => void renew(certificate)}><CopyPlus size={14} />Clone / Renew</button>}{canIssue && <button className="secondary-button" onClick={() => void createRevision(certificate)}>Create revision</button>}{canIssue && <button className="secondary-button" onClick={() => void repair(certificate)}>Repair issuance</button>}{canIssue && String(certificate.status) !== "revoked" && <button className="danger-button" onClick={() => void revoke(Number(certificate.id))}>Revoke</button>}</div>; }
  const active = useMemo(() => certs.filter((certificate) => String(certificate.status) === "valid").length, [certs]);
  function navigateToSection(section: "certificates" | "archive" | "print") {
    setActiveSection(section);
    const target = section === "certificates" ? certificatesSectionRef.current : section === "archive" ? archiveSectionRef.current : printSectionRef.current;
    target?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  return <>
    <div className="page-heading"><div><p className="eyebrow">CERTIFICATE GENERATION</p><h1>Certificates</h1><span>{total} certificate(s) found - {active} valid on this page</span></div></div>
    <nav className="certificate-section-nav" aria-label="Certificate sections">
      <button className={activeSection === "certificates" ? "active" : ""} aria-current={activeSection === "certificates" ? "page" : undefined} onClick={() => navigateToSection("certificates")}>Certificates</button>
      <button className={activeSection === "archive" ? "active" : ""} aria-current={activeSection === "archive" ? "page" : undefined} onClick={() => navigateToSection("archive")}>Archive</button>
      <button className={activeSection === "print" ? "active" : ""} aria-current={activeSection === "print" ? "page" : undefined} onClick={() => navigateToSection("print")}>Print Certificates</button>
    </nav>
    {error && <div className="inline-alert form-error"><span>{error}</span><button onClick={() => setError("")}><X size={16} /></button></div>}
    {message && <div className="inline-alert form-success">{message}</div>}
    <section ref={certificatesSectionRef} className="certificate-section-anchor"><Panel title="Approved inspections ready for certificate"><div className="table-scroll"><table><thead><tr><th>Reference</th><th>Client</th><th>Equipment</th><th>Type</th><th>Status</th><th /></tr></thead><tbody>{inspections.map((row) => <tr key={row.id} className="clickable-row" onClick={() => onOpenInspection?.(Number(row.id))}><td className="mono">{row.reference}</td><td>{row.client_name}</td><td>{row.asset_code} - {row.equipment_name}</td><td>{row.category_name}</td><td><StatusBadge status={row.status} /></td><td onClick={(event) => event.stopPropagation()}>{canIssue && <button className="primary-button" onClick={() => { const certificate = certs.find((item) => Number(item.inspection_id) === Number(row.id)); if (row.status === "issued" && certificate) void openPdf(certificate); else void generate(Number(row.id)); }}>{row.status === "issued" ? "Open Certificate" : "Check & issue"}</button>}</td></tr>)}</tbody></table></div></Panel></section>
    <section ref={archiveSectionRef} className="certificate-section-anchor"><Panel title="Issued certificate archive">
      <form ref={printSectionRef} className="archive-filter-form certificate-section-anchor" onSubmit={applyFilters}>
        <label className="archive-search"><span>Search</span><div><Search size={16} /><input value={filters.q} onChange={(event) => setFilters({ ...filters, q: event.target.value })} placeholder="Certificate, client, equipment, serial or inspector" /></div></label>
        <label><span>Client</span><select value={filters.client_id} onChange={(event) => setFilters({ ...filters, client_id: event.target.value })}><option value="">All clients</option>{filterOptions.clients.map((client) => <option key={client.id} value={client.id}>{client.name}</option>)}</select></label>
        <label><span>Category</span><select value={filters.category_id} onChange={(event) => setFilters({ ...filters, category_id: event.target.value })}><option value="">All categories</option>{filterOptions.categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select></label>
        <label><span>Status</span><select value={filters.status} onChange={(event) => setFilters({ ...filters, status: event.target.value })}><option value="">All statuses</option><option value="valid">Valid</option><option value="expired">Expired</option><option value="revoked">Revoked</option><option value="superseded">Superseded</option></select></label>
        <fieldset><legend>Date of Thorough Examination</legend><label><span>From date</span><input type="date" value={filters.inspection_from} onChange={(event) => setFilters({ ...filters, inspection_from: event.target.value })} /></label><label><span>To date</span><input type="date" value={filters.inspection_to} onChange={(event) => setFilters({ ...filters, inspection_to: event.target.value })} /></label></fieldset>
        <div className="archive-filter-actions"><button className="primary-button" type="submit">Apply filters</button><button className="secondary-button" type="button" onClick={resetFilters}>Reset filters</button></div>
      </form>
      <div className={`certificate-batch-bar ${selectedRows.size === 0 ? "certificate-batch-bar-empty" : ""}`} role="region" aria-label="Certificate batch actions" aria-live="polite">
        <div className="certificate-batch-count"><strong>{selectedRows.size} certificate{selectedRows.size === 1 ? "" : "s"} selected</strong><span>{visibleSelected} selected on this page</span></div>
        <div><button className="primary-button" disabled={batchBusy || selectedRows.size === 0} onClick={() => void runBatch("attachment")}><Download size={16} />{batchBusy ? "Preparing..." : "Download Combined PDF"}</button><button className="secondary-button" disabled={batchBusy || selectedRows.size === 0} onClick={() => void runBatch("inline")}><Printer size={16} />Print Selected</button><button className="link-button" disabled={batchBusy || selectedRows.size === 0} onClick={() => setSelectedRows(new Map())}>Clear selection</button></div>
      </div>
      <div className="table-scroll archive-table-wrap"><table className="archive-table batch-archive-table"><thead><tr><th className="certificate-select-column"><label title="Select all visible results"><input ref={selectAllRef} type="checkbox" checked={allVisibleSelected} onChange={(event) => toggleVisible(event.target.checked)} /><span className="sr-only">Select all visible results</span></label></th><th>Certificate</th><th>Client</th><th>Equipment</th><th>Category</th><th>Examination date</th><th>Status</th><th>Actions</th></tr></thead><tbody>{certs.map((certificate) => <tr key={certificate.id} className={`clickable-row ${detailSelection?.id === certificate.id ? "selected-row" : ""}`} onClick={() => void openDetail(certificate)}><td className="certificate-select-column" onClick={(event) => event.stopPropagation()}><input type="checkbox" aria-label={`Select ${certificate.certificate_number}`} checked={selectedRows.has(Number(certificate.id))} onChange={(event) => toggleCertificate(certificate, event.target.checked)} /></td><td className="mono">{certificate.certificate_number}<small>Revision {certificate.revision}</small></td><td title={certificate.client_name}>{certificate.client_name}</td><td>{certificate.asset_code} - {certificate.equipment_name}</td><td>{certificate.category_name}</td><td>{certificate.inspection_date || "-"}</td><td><StatusBadge status={certificate.status} /></td><td>{primaryActions(certificate)}</td></tr>)}</tbody></table>
        <div className="certificate-card-list">{certs.map((certificate) => <article key={`card-${certificate.id}`} className="certificate-card" onClick={() => void openDetail(certificate)}><header><label onClick={(event) => event.stopPropagation()}><input type="checkbox" aria-label={`Select ${certificate.certificate_number}`} checked={selectedRows.has(Number(certificate.id))} onChange={(event) => toggleCertificate(certificate, event.target.checked)} /></label><strong>{certificate.certificate_number}</strong><StatusBadge status={certificate.status} /></header><dl><div><dt>Client</dt><dd>{certificate.client_name}</dd></div><div><dt>Category</dt><dd>{certificate.category_name}</dd></div><div><dt>Equipment</dt><dd>{certificate.asset_code} - {certificate.equipment_name}</dd></div><div><dt>Examination date</dt><dd>{certificate.inspection_date || "-"}</dd></div></dl><footer onClick={(event) => event.stopPropagation()}>{primaryActions(certificate)}</footer></article>)}</div>
        {!certs.length && <div className="empty-state"><Search size={32} /><strong>No matching certificates</strong><span>Change the filters and apply again.</span></div>}
      </div>
      <div className="pagination"><button disabled={page <= 1} onClick={() => setPage((current) => current - 1)}>Previous</button><span>Page {page} of {pages} - page checkbox selects {certs.length} visible result(s) only</span><button disabled={page >= pages} onClick={() => setPage((current) => current + 1)}>Next</button></div>
    </Panel></section>
    {detail?.certificate && detailSelection && <div className="drawer-backdrop" onClick={() => { setDetailSelection(null); setDetail(null); }}><aside className="record-drawer" onClick={(event) => event.stopPropagation()}><header><div><span className="eyebrow">CERTIFICATE RECORD</span><h2>{detail.certificate.certificate_number}</h2><p>{detail.certificate.client_name} - {detail.certificate.asset_code} - {detail.certificate.equipment_name}</p></div><button className="icon-button" onClick={() => { setDetailSelection(null); setDetail(null); }} title="Close"><X size={20} /></button></header><dl className="record-detail-list"><div><dt>Status</dt><dd><StatusBadge status={detail.certificate.status} /></dd></div><div><dt>Revision</dt><dd>v{detail.certificate.revision}</dd></div><div><dt>Inspection</dt><dd className="mono">{detail.certificate.inspection_reference}</dd></div><div><dt>Issued</dt><dd>{detail.certificate.issued_at}</dd></div><div><dt>Expires</dt><dd>{detail.certificate.expires_at}</dd></div><div><dt>Inspector</dt><dd>{detail.certificate.inspector_name}</dd></div></dl>{drawerActions(detail.certificate)}<h3>Notification delivery</h3><div className="notification-list">{notifications.length === 0 && <p>No notification has been queued for this certificate.</p>}{notifications.map((notice) => <div className="notification-row" key={notice.id}><span>{notice.recipient_email}</span><StatusBadge status={notice.status} /><small>Revision {notice.revision} - Attempts {notice.attempts}/{notice.max_attempts}{notice.last_error ? ` - ${notice.last_error}` : ""}</small>{canIssue && notice.status !== "sent" && <button className="secondary-button" onClick={() => void resend(Number(notice.id))}><Mail size={14} />Resend</button>}</div>)}</div><h3>Revision history</h3><div className="revision-list">{(detail.revisions || []).map((revision: Row) => <div key={revision.revision}><strong>v{revision.revision}</strong><span>{revision.created_at}</span><code>{String(revision.pdf_hash).slice(0, 16)}...</code></div>)}</div></aside></div>}
  </>;
}