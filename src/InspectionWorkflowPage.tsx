import { useEffect, useMemo, useRef, useState, type ReactNode } from "react";
import { AlertCircle, CheckCircle2, ChevronLeft, ChevronRight, CopyPlus, Pencil, Plus, Search, Trash2 } from "lucide-react";

type RoleLike = { slug: string; permissions: string[] };
type UserLike = { id: number; role: RoleLike };
type ApiResponse<T> = { data: T | null; error: string | null; validation?: Record<string, string> | null };
type ApiRequest = <T>(path: string, options?: RequestInit, csrf?: string) => Promise<ApiResponse<T>>;
type RecordRow = Record<string, any>;
type ItemRows = Record<string, Array<Record<string, string>>>;

function describeApiFailure(error: string | null | undefined, validation: unknown): string {
  const messages: string[] = [];
  const collect = (value: unknown) => {
    if (typeof value === "string" && value.trim()) messages.push(value.trim());
    else if (Array.isArray(value)) value.forEach(collect);
    else if (value && typeof value === "object") Object.values(value as Record<string, unknown>).forEach(collect);
  };
  collect(validation);
  return [error || "The action could not be completed.", ...Array.from(new Set(messages)).map((item) => `- ${item}`)].join("\n");
}

type Props = {
  csrf: string;
  openInspectionId?: number | null;
  user: UserLike;
  apiBase: string;
  request: ApiRequest;
};

const wizardSteps = [
  { id: "records", label: "Records", description: "Client, equipment and category" },
  { id: "details", label: "Details", description: "Generated reference, dates and location" },
  { id: "fields", label: "Inspection fields", description: "Dynamic fields and category checklists" },
  { id: "evidence", label: "Evidence", description: "Photos, signature and remarks" },
  { id: "review", label: "Review & submit", description: "Confirm and issue" },
] as const;

function can(user: UserLike, permission: string) {
  return user.role.permissions.includes("*") || user.role.permissions.includes(permission);
}

function isPrivileged(user: UserLike) {
  return ["super-admin", "operations-admin"].includes(user.role.slug);
}

function tone(status: string) {
  const normal = status.toLowerCase();
  if (["valid", "approved", "issued", "active"].includes(normal)) return "valid";
  if (["submitted", "pending", "correction", "expiring", "expired soon"].includes(normal)) return "warning";
  if (["revoked", "expired", "suspended"].includes(normal)) return "danger";
  return "neutral";
}

function StatusBadge({ status }: { status: string }) {
  return <span className={`status-badge status-${tone(status)}`}>{status}</span>;
}

function Panel({ title, action, children }: { title: string; action?: ReactNode; children: ReactNode }) {
  return <section className="panel"><header className="panel-header"><h2>{title}</h2>{action}</header>{children}</section>;
}

function buildEmptyRow(section: RecordRow) {
  const row: Record<string, string> = {};
  for (const column of section.columns || []) {
    row[String(column.column_key)] = "";
  }
  return row;
}

function validateSectionRows(section: RecordRow, rows: Array<Record<string, string>>) {
  const errors: string[] = [];
  const minRows = Number(section.min_rows || 0);
  if (rows.length < minRows) {
    errors.push(`${section.label} requires at least ${minRows} row(s).`);
  }
  for (const [rowIndex, row] of rows.entries()) {
    for (const column of section.columns || []) {
      if (Number(column.is_required) === 1 && !(row[String(column.column_key)] || "").trim()) {
        errors.push(`${section.label}: row ${rowIndex + 1} requires ${column.label}.`);
      }
    }
  }
  return errors;
}

function stepErrors(step: number, form: Record<string, string>, fields: RecordRow[], values: Record<string, string>, sections: RecordRow[], items: ItemRows) {
  const errors: string[] = [];
  if (step === 0) {
    if (!form.client_id) errors.push("Select a client.");
    if (!form.equipment_id) errors.push("Select equipment.");
    if (!form.category_id) errors.push("Select a certification category.");
  }
  if (step === 1) {
    if (!form.inspection_date) errors.push("Choose an inspection date.");
    if (!form.next_due_date) errors.push("Choose a next due date.");
    if (form.next_due_date && form.inspection_date && form.next_due_date < form.inspection_date) {
      errors.push("Next due date cannot be earlier than the inspection date.");
    }
  }
  if (step === 2) {
    for (const field of fields) {
      if (Number(field.is_required) === 1 && !["photo", "signature"].includes(String(field.field_type)) && !(values[String(field.id)] || "").trim()) {
        errors.push(`${field.label} is required.`);
      }
    }
    for (const section of sections) {
      errors.push(...validateSectionRows(section, items[String(section.section_key)] || []));
    }
  }
  return errors;
}

export function EnhancedInspectionWorkflowPage({ csrf, user, apiBase, request, openInspectionId }: Props) {
  const [rows, setRows] = useState<RecordRow[]>([]);
  const [clients, setClients] = useState<RecordRow[]>([]);
  const [equipment, setEquipment] = useState<RecordRow[]>([]);
  const [categories, setCategories] = useState<RecordRow[]>([]);
  const [fields, setFields] = useState<RecordRow[]>([]);
  const [sections, setSections] = useState<RecordRow[]>([]);
  const [values, setValues] = useState<Record<string, string>>({});
  const [items, setItems] = useState<ItemRows>({});
  const [form, setForm] = useState<Record<string, string>>({ inspection_date: new Date().toISOString().slice(0, 10) });
  const [step, setStep] = useState(0);
  const [evidence, setEvidence] = useState<File | null>(null);
  const [signature, setSignature] = useState<File | null>(null);
  const [uploadStatus, setUploadStatus] = useState<Record<string, string>>({ evidence: "Optional - no file selected", signature: "Optional - no file selected" });
  const [attachments, setAttachments] = useState<RecordRow[]>([]);
  const [reviewComments, setReviewComments] = useState<RecordRow[]>([]);
  const [readiness, setReadiness] = useState<RecordRow | null>(null);
  const [actionState, setActionState] = useState<"" | "saving" | "previewing" | "issuing">("");
  const [lastSavedAt, setLastSavedAt] = useState("");
  const [previewUrl, setPreviewUrl] = useState("");
  const [issuedCertificate, setIssuedCertificate] = useState<RecordRow | null>(null);
  const [statusFilter, setStatusFilter] = useState("correction");
  const [searchQuery, setSearchQuery] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [comment, setComment] = useState<Record<number, string>>({});
  const [submitting, setSubmitting] = useState(false);
  const [referencePreview, setReferencePreview] = useState("");
  const [referenceHint, setReferenceHint] = useState("Select client and category to preview the next serial.");
  const [sourceReference, setSourceReference] = useState("");
  const wizardTopRef = useRef<HTMLDivElement | null>(null);

  const privilegedSubmitter = isPrivileged(user);
  const currentStepErrors = useMemo(() => stepErrors(step, form, fields, values, sections, items), [step, form, fields, values, sections, items]);
  const filteredEquipment = useMemo(() => equipment.filter((item) => String(item.status || "active") === "active" && (!form.client_id || String(item.client_id) === String(form.client_id))), [equipment, form.client_id]);
  const lifecycleLabel = (status: string) => ({ draft: "Draft", submitted: "Submitted", correction: "Returned", approved: "Approved", issued: "Certificate Issued", revoked: "Revoked", expired: "Expired" }[status] || status);
  const isEditingDraft = Boolean(form.id);
  const displayedReference = form.reference || referencePreview || "";
  const selectedCategory = categories.find((item) => String(item.id) === String(form.category_id || ""));
  const selectedEquipment = equipment.find((item) => String(item.id) === String(form.equipment_id || ""));
  const isGeneralCategory = ["general_lifting_accessory", "general_thorough_examination"].includes(String(selectedCategory?.template_family || ""));
  const dedicatedSuggestions = useMemo(() => {
    if (!isGeneralCategory || !selectedEquipment) return [];
    const words = String(selectedEquipment.name || "").toLowerCase().split(/[^a-z0-9]+/).filter((word) => word.length > 2);
    return categories.filter((item) => !String(item.template_family || "").startsWith("general_") && words.some((word) => String(item.name || "").toLowerCase().includes(word))).slice(0, 5);
  }, [categories, isGeneralCategory, selectedEquipment]);

  async function load() {
    setError("");
    const [i, c, e, cat] = await Promise.all([
      request<{ inspections: RecordRow[] }>("/inspections/index.php"),
      request<{ clients: RecordRow[] }>("/clients/index.php"),
      request<{ equipment: RecordRow[] }>("/equipment/index.php"),
      request<{ categories: RecordRow[] }>("/categories/index.php?status=active"),
    ]);
    if (i.data) setRows(i.data.inspections);
    if (c.data) setClients(c.data.clients);
    if (e.data) setEquipment(e.data.equipment);
    if (cat.data) setCategories(cat.data.categories);
    if (i.error) setError(i.error);
  }

  useEffect(() => { void load(); }, []);

  useEffect(() => {
    if (form.id) {
      setReferencePreview(form.reference || "");
      setReferenceHint(sourceReference ? `Renewal draft cloned from ${sourceReference}.` : "Allocated reference reserved for this draft.");
      return;
    }
    if (!form.client_id || !form.category_id) {
      setReferencePreview("");
      setReferenceHint("Select client and category to preview the next serial.");
      return;
    }
    let cancelled = false;
    void (async () => {
      const res = await request<{ preview: RecordRow }>(`/inspections/next-reference.php?client_id=${encodeURIComponent(form.client_id)}&category_id=${encodeURIComponent(form.category_id)}`);
      if (cancelled) return;
      if (res.error || !res.data?.preview) {
        setReferencePreview("");
        setReferenceHint(res.error || "Unable to preview the next serial yet.");
        return;
      }
      setReferencePreview(String(res.data.preview.reference || ""));
      setReferenceHint("This serial is reserved when you save the draft. Gaps are intentional for auditability.");
    })();
    return () => {
      cancelled = true;
    };
  }, [form.client_id, form.category_id, form.id, form.reference, request, sourceReference]);

  function initializeSections(sectionRows: RecordRow[], seedItems?: ItemRows) {
    const nextItems: ItemRows = {};
    for (const section of sectionRows) {
      const key = String(section.section_key);
      const seeded = seedItems?.[key] || [];
      nextItems[key] = seeded.length > 0
        ? seeded.map((row) => ({ ...buildEmptyRow(section), ...row }))
        : (Number(section.min_rows || 0) > 0 ? Array.from({ length: Number(section.min_rows) }, () => buildEmptyRow(section)) : []);
    }
    setSections(sectionRows);
    setItems(nextItems);
  }

  async function loadFields(categoryId: string) {
    setFields([]);
    setSections([]);
    setItems({});
    setValues({});
    setForm((current) => ({ ...current, category_id: categoryId }));
    if (!categoryId) return;
    const res = await request<{ fields: RecordRow[]; sections?: RecordRow[] }>(`/categories/fields.php?category_id=${categoryId}`);
    if (res.error) {
      setError(res.error);
      return;
    }
    setFields(res.data?.fields || []);
    initializeSections(res.data?.sections || []);
  }

  function setField(key: string, value: string) {
    setForm((current) => {
      const next = { ...current, [key]: value };
      if (key === "client_id" && current.client_id !== value && !current.id) {
        next.equipment_id = "";
      }
      return next;
    });
  }

  function setDynamicValue(fieldId: number, value: string) {
    setValues((current) => ({ ...current, [String(fieldId)]: value }));
  }

  function updateSectionCell(sectionKey: string, rowIndex: number, columnKey: string, value: string) {
    setItems((current) => ({
      ...current,
      [sectionKey]: (current[sectionKey] || []).map((row, index) => index === rowIndex ? { ...row, [columnKey]: value } : row),
    }));
  }

  function addSectionRow(section: RecordRow) {
    const key = String(section.section_key);
    const maxRows = section.max_rows ? Number(section.max_rows) : null;
    setItems((current) => {
      const existing = current[key] || [];
      if (maxRows !== null && existing.length >= maxRows) {
        return current;
      }
      return { ...current, [key]: [...existing, buildEmptyRow(section)] };
    });
  }

  function removeSectionRow(section: RecordRow, rowIndex: number) {
    const key = String(section.section_key);
    const minRows = Number(section.min_rows || 0);
    setItems((current) => {
      const existing = current[key] || [];
      if (existing.length <= minRows) {
        return current;
      }
      return { ...current, [key]: existing.filter((_, index) => index !== rowIndex) };
    });
  }

  async function uploadFile(id: number, file: File | null, attachmentType: "evidence" | "signature") {
    if (!file) return null;
    if (file.size <= 0) return `${file.name} is empty and was not uploaded.`;
    if (file.size > 8 * 1024 * 1024) return `${file.name} is larger than the 8MB upload limit.`;
    const fd = new FormData();
    fd.append("inspection_id", String(id));
    fd.append("file", file);
    fd.append("attachment_type", attachmentType);
    const headers = new Headers();
    headers.set("X-CSRF-Token", csrf);
    const r = await fetch(`${apiBase}/inspections/upload.php`, { method: "POST", body: fd, headers, credentials: "include" });
    const payload = await r.json().catch(() => ({ error: "Upload failed." }));
    if (!r.ok) return `${file.name}: ${payload.error || "Upload failed."}`;
    return null;
  }

  async function removeAttachment(attachmentId: number) {
    setError("");
    const result = await request("/inspections/attachment-remove.php", {
      method: "POST",
      body: JSON.stringify({ attachment_id: attachmentId }),
    }, csrf);
    if (result.error) {
      setError(describeApiFailure(result.error, result.validation));
      return;
    }
    setAttachments((current) => current.filter((item) => Number(item.id) !== attachmentId));
    if (form.id) await refreshReadiness(Number(form.id));
    setMessage("Attachment removed. Evidence and inspection signatures are optional unless the category template explicitly requires them.");
  }
  function renderField(field: RecordRow) {
    const value = values[String(field.id)] || "";
    const type = String(field.field_type);
    const placeholder = field.placeholder_text || "";
    if (type === "textarea") return <textarea value={value} onChange={(e) => setDynamicValue(Number(field.id), e.target.value)} placeholder={placeholder} />;
    if (type === "select" || type === "pass_fail") {
      const options = type === "pass_fail" ? ["Pass", "Fail", "N/A"] : JSON.parse(field.options_json || "[]");
      return <select value={value} onChange={(e) => setDynamicValue(Number(field.id), e.target.value)}><option value="">Select</option>{options.map((option: string) => <option key={option} value={option}>{option}</option>)}</select>;
    }
    if (type === "checkbox") {
      const choices = JSON.parse(field.options_json || "[]");
      if (choices.length) {
        const selected = value.split(",").map((item: string) => item.trim()).filter(Boolean);
        return <div className="dynamic-option-grid">{choices.map((choice: string) => <label key={choice}><input type="checkbox" checked={selected.includes(choice)} onChange={(e) => { const next = e.target.checked ? [...selected, choice] : selected.filter((item: string) => item !== choice); setDynamicValue(Number(field.id), next.join(", ")); }} />{choice}</label>)}</div>;
      }
      return <select value={value} onChange={(e) => setDynamicValue(Number(field.id), e.target.value)}><option value="">Select</option><option value="Yes">Yes</option><option value="No">No</option></select>;
    }
    return <input type={type === "date" ? "date" : type === "number" ? "number" : "text"} value={value} onChange={(e) => setDynamicValue(Number(field.id), e.target.value)} placeholder={placeholder} />;
  }

  function renderSectionCell(sectionKey: string, rowIndex: number, column: RecordRow) {
    const value = items[sectionKey]?.[rowIndex]?.[String(column.column_key)] || "";
    const type = String(column.column_type);
    if (type === "textarea") {
      return <textarea value={value} onChange={(e) => updateSectionCell(sectionKey, rowIndex, String(column.column_key), e.target.value)} placeholder={column.placeholder_text || ""} />;
    }
    if (type === "select" || type === "pass_fail") {
      const options = type === "pass_fail" ? ["Pass", "Fail", "N/A"] : JSON.parse(column.options_json || "[]");
      return <select value={value} onChange={(e) => updateSectionCell(sectionKey, rowIndex, String(column.column_key), e.target.value)}><option value="">Select</option>{options.map((option: string) => <option key={option} value={option}>{option}</option>)}</select>;
    }
    if (type === "checkbox") {
      return <select value={value} onChange={(e) => updateSectionCell(sectionKey, rowIndex, String(column.column_key), e.target.value)}><option value="">Select</option><option value="Yes">Yes</option><option value="No">No</option></select>;
    }
    return <input type={type === "date" ? "date" : type === "number" ? "number" : "text"} value={value} onChange={(e) => updateSectionCell(sectionKey, rowIndex, String(column.column_key), e.target.value)} placeholder={column.placeholder_text || ""} />;
  }

  function resetWizard() {
    setForm({ inspection_date: new Date().toISOString().slice(0, 10) });
    setValues({});
    setFields([]);
    setSections([]);
    setItems({});
    setEvidence(null);
    setSignature(null);
    setAttachments([]);
    setReviewComments([]);
    setReadiness(null);
    setLastSavedAt("");
    setStep(0);
    setReferencePreview("");
    setReferenceHint("Select client and category to preview the next serial.");
    setSourceReference("");
  }

  function loadDraftIntoWizard(inspection: RecordRow, fieldRows: RecordRow[], sectionRows: RecordRow[], itemRows: ItemRows, source?: string) {
    const mappedValues: Record<string, string> = {};
    for (const field of fieldRows) {
      if (field.value_text !== null && field.value_text !== undefined) {
        mappedValues[String(field.id)] = String(field.value_text);
      }
    }
    setForm({
      id: String(inspection.id),
      client_id: String(inspection.client_id),
      equipment_id: String(inspection.equipment_id),
      category_id: String(inspection.category_id),
      reference: String(inspection.reference || ""),
      inspection_date: String(inspection.inspection_date || new Date().toISOString().slice(0, 10)),
      next_due_date: String(inspection.next_due_date || ""),
      location: String(inspection.location || ""),
      remarks: String(inspection.remarks || ""),
      result: String(inspection.result || "pending"),
    });
    setFields(fieldRows);
    setValues(mappedValues);
    initializeSections(sectionRows, itemRows);
    setEvidence(null);
    setSignature(null);
    setSourceReference(source || String(inspection.cloned_from_reference || ""));
    setStep(1);
    window.setTimeout(() => wizardTopRef.current?.scrollIntoView({ behavior: "smooth", block: "start" }), 40);
  }

  async function createInspection(submitNow: boolean): Promise<boolean> {
    setError("");
    setMessage("");
    setSubmitting(true);
    setActionState(submitNow ? "issuing" : "saving");

    const blockingErrors = stepErrors(0, form, fields, values, sections, items)
      .concat(stepErrors(1, form, fields, values, sections, items))
      .concat(stepErrors(2, form, fields, values, sections, items));

    if (submitNow && blockingErrors.length) {
      setError(`Certificate could not be issued. Complete:\n- ${blockingErrors.join("\n- ")}`);
      setSubmitting(false);
      setActionState("");
      return false;
    }

    const body: Record<string, any> = {
      client_id: Number(form.client_id),
      equipment_id: Number(form.equipment_id),
      category_id: Number(form.category_id),
      inspection_date: form.inspection_date,
      next_due_date: form.next_due_date || null,
      location: form.location || "",
      remarks: form.remarks || "",
      result: form.result || "pending",
      draft_only: !submitNow,
      values,
      items,
    };
    if (form.id) body.id = Number(form.id);

    const res = await request<{ inspection: RecordRow }>("/inspections/index.php", { method: "POST", body: JSON.stringify(body) }, csrf);
    if (res.error || !res.data) {
      setError(describeApiFailure(res.error || (form.id ? "Unable to update inspection." : "Unable to create inspection."), res.validation));
      setSubmitting(false);
      setActionState("");
      return false;
    }

    const inspection = res.data.inspection;
    const id = Number(inspection.id);
    setForm((current) => ({ ...current, id: String(id), reference: String(inspection.reference) }));
    setReferencePreview(String(inspection.reference || ""));
    setUploadStatus({ evidence: evidence ? "Pending upload" : "No new evidence selected", signature: signature ? "Pending upload" : "No new signature selected" });

    const evidenceError = await uploadFile(id, evidence, "evidence");
    setUploadStatus((current) => ({ ...current, evidence: evidence ? (evidenceError || "Uploaded") : "No new evidence selected" }));
    const signatureError = await uploadFile(id, signature, "signature");
    setUploadStatus((current) => ({ ...current, signature: signature ? (signatureError || "Uploaded") : "No new signature selected" }));
        const uploadFailures = [evidenceError, signatureError].filter(Boolean) as string[];
    if (uploadFailures.length) {
      setError(`Draft values saved successfully. ${uploadFailures.length} optional attachment(s) did not upload:\n- ${uploadFailures.join("\n- ")}\nYou can retry those uploads later; they do not block this category.`);
    }

    setEvidence(null);
    setSignature(null);
    const detail = await request<{ attachments?: RecordRow[]; comments?: RecordRow[] }>(`/inspections/detail.php?id=${id}`);
    if (detail.data) {
      setAttachments(detail.data.attachments || []);
      setReviewComments(detail.data.comments || []);
    }
    const currentReadiness = await refreshReadiness(id);
    setLastSavedAt(new Date().toLocaleTimeString());

    if (submitNow) {
      if (!currentReadiness || !currentReadiness.ready) {
        const firstBlocker = (currentReadiness?.blocking_items || [])[0] as RecordRow | undefined;
        if (firstBlocker?.step) setStep(Math.max(0, Number(firstBlocker.step) - 1));
        setError(currentReadiness ? "Certificate issuance stopped. Complete the exact readiness items shown below." : "Certificate issuance stopped because readiness could not be verified.");
        setSubmitting(false);
        setActionState("");
        return false;
      }
      const submitRes = await request<{ message?: string; auto_issued?: boolean; certificate?: RecordRow | null }>(
        "/inspections/status.php",
        { method: "POST", body: JSON.stringify({ id, status: "submitted", create_revision: true, comment: privilegedSubmitter ? "Corrected inspection resubmitted by privileged administrator." : "Corrected inspection resubmitted for review." }) },
        csrf,
      );
      if (submitRes.error) {
        setError(describeApiFailure(submitRes.error, submitRes.validation));
        setSubmitting(false);
        setActionState("");
        await load();
        return false;
      }
      const cert = submitRes.data?.certificate;
      if (submitRes.data?.auto_issued && cert) {
        setMessage(`Inspection updated and certificate issued successfully: ${cert.certificate_number}.`);
        setIssuedCertificate(cert);
        if (cert.pdf_url) window.open(String(cert.pdf_url), "_blank", "noopener");
        resetWizard();
      } else {
        setMessage(submitRes.data?.message || `Inspection ${inspection.reference} updated and resubmitted for review.`);
      }
    } else {
      setMessage(`Draft updated successfully: ${inspection.reference}.`);
      setReferenceHint(sourceReference ? `Renewal draft cloned from ${sourceReference}.` : "Allocated reference reserved for this draft.");
    }

    setSubmitting(false);
    setActionState("");
    await load();
    return true;
  }

  async function setStatus(id: number, status: string, note?: string) {
    setError("");
    const res = await request<{ message?: string; auto_issued?: boolean; certificate?: RecordRow | null; partial_error?: string | null }>(
      "/inspections/status.php",
      { method: "POST", body: JSON.stringify({ id, status, comment: note || comment[id] || "" }) },
      csrf,
    );
    if (res.error) {
      setError(res.error);
      return;
    }
    const cert = res.data?.certificate;
    if (res.data?.auto_issued && cert) {
      setMessage(`Inspection submitted and certificate issued: ${cert.certificate_number}`);
    } else if (res.data?.partial_error) {
      setMessage(`${res.data.message || "Inspection updated."} ${res.data.partial_error}`);
    } else {
      setMessage(res.data?.message || "Inspection status updated.");
    }
    setComment((current) => ({ ...current, [id]: "" }));
    await load();
  }

  async function addComment(id: number) {
    const text = comment[id] || "";
    if (!text) return;
    const res = await request("/inspections/comments.php", { method: "POST", body: JSON.stringify({ inspection_id: id, comment_text: text, comment_type: "comment" }) }, csrf);
    if (res.error) {
      setError(res.error);
      return;
    }
    setMessage("Comment added.");
    setComment((current) => ({ ...current, [id]: "" }));
  }

  async function refreshReadiness(id: number): Promise<RecordRow | null> {
    const result = await request<RecordRow>(`/certificates/readiness.php?inspection_id=${id}`);
    if (result.error || !result.data) {
      setReadiness(null);
      setError(describeApiFailure(result.error || "Certificate readiness could not be checked.", result.validation));
      return null;
    }
    setReadiness(result.data);
    return result.data;
  }

  async function editInspection(id: number) {
    setError("");
    const res = await request<{ inspection: RecordRow; fields: RecordRow[]; sections?: RecordRow[]; items?: ItemRows; attachments?: RecordRow[]; comments?: RecordRow[] }>(`/inspections/detail.php?id=${encodeURIComponent(String(id))}`);
    if (res.error || !res.data) {
      setError(describeApiFailure(res.error || "Unable to load inspection.", res.validation));
      return;
    }
    loadDraftIntoWizard(res.data.inspection, res.data.fields || [], res.data.sections || [], res.data.items || {}, res.data.inspection?.cloned_from_reference || "");
    setAttachments(res.data.attachments || []);
    setReviewComments(res.data.comments || []);
    setUploadStatus({
      evidence: (res.data.attachments || []).some((item) => String(item.attachment_type || "evidence") === "evidence") ? "Existing evidence uploaded" : "Optional - no evidence uploaded",
      signature: (res.data.attachments || []).some((item) => String(item.attachment_type || "evidence") === "signature") ? "Existing inspection signature uploaded" : "Optional - profile signature or empty area will be used",
    });
    await refreshReadiness(id);
    setMessage(`Returned inspection ${res.data.inspection.reference} loaded for correction.`);
  }

  useEffect(() => {
    if (openInspectionId) {
      void editInspection(openInspectionId);
      return;
    }
    resetWizard();
  }, [openInspectionId]);
  useEffect(() => { if (form.id) void refreshReadiness(Number(form.id)); }, [form.id]);

  async function cloneInspection(id: number) {
    setError("");
    const res = await request<{ inspection: RecordRow; fields: RecordRow[]; sections?: RecordRow[]; items?: ItemRows; source_reference?: string }>(
      "/inspections/clone.php",
      { method: "POST", body: JSON.stringify({ inspection_id: id }) },
      csrf,
    );
    if (res.error || !res.data) {
      setError(res.error || "Unable to create renewal draft.");
      return;
    }
    loadDraftIntoWizard(res.data.inspection, res.data.fields || [], res.data.sections || [], res.data.items || {}, res.data.source_reference || "");
    setMessage(`Renewal draft created: ${res.data.inspection.reference}`);
  }

  async function previewCertificate() {
    if (!form.id) {
      setError("Save the draft before previewing the certificate.");
      return;
    }
    const saved = await createInspection(false);
    if (!saved) return;
    setError("");
    setMessage("");
    setSubmitting(true);
    setActionState("previewing");
    const response = await request<{ success: boolean; preview_url: string }>("/certificates/preview.php", {
      method: "POST",
      body: JSON.stringify({ inspection_id: Number(form.id) }),
    }, csrf);
    setSubmitting(false);
    setActionState("");
    if (response.error || !response.data?.preview_url) {
      setError(describeApiFailure(response.error || "Preview could not be generated.", response.validation));
      return;
    }
    setPreviewUrl(response.data.preview_url);
    const opened = window.open(response.data.preview_url, "_blank", "noopener");
    setMessage(opened ? "Certificate preview generated successfully." : "Certificate preview is ready. Use Open prepared preview below.");
  }
  function goNext() {
    if (currentStepErrors.length) {
      setError(currentStepErrors[0]);
      return;
    }
    setError("");
    setStep((current) => Math.min(current + 1, wizardSteps.length - 1));
  }

  function goBack() {
    setError("");
    setStep((current) => Math.max(current - 1, 0));
  }

  const selectedSummaryEquipment = filteredEquipment.find((item) => String(item.id) === String(form.equipment_id));
  const summaryRows = [
    ["Client", clients.find((item) => String(item.id) === String(form.client_id))?.name || "-"],
    ["Equipment", selectedSummaryEquipment ? `${selectedSummaryEquipment.asset_code} - ${selectedSummaryEquipment.name}` : "-"],
    ["Category", categories.find((item) => String(item.id) === String(form.category_id))?.name || "-"],
    ["Reference", displayedReference || "-"],
    ["Inspection date", form.inspection_date || "-"],
    ["Next due date", form.next_due_date || "-"],
    ["Location", form.location || "-"],
    ["Evidence file", evidence?.name || "No new file uploaded"],
    ["Signature", signature?.name || "No new file uploaded"],
  ];

  const filteredRows = useMemo(() => {
    const query = searchQuery.trim().toLowerCase();
    return rows.filter((row) => {
      if (statusFilter && String(row.status) !== statusFilter) return false;
      if (!query) return true;
      return [row.reference, row.client_name, row.equipment_name, row.asset_code, row.category_name].some((value) => String(value || "").toLowerCase().includes(query));
    });
  }, [rows, statusFilter, searchQuery]);

  const readinessItems: RecordRow[] = readiness
    ? (readiness.blocking_items || [
        ...(readiness.missing_fields || []),
        ...Object.entries(readiness.validation_errors || {}).map(([key, reason]) => ({ key, label: key.replace(/_/g, " "), reason, section: "Inspection fields", step: 3 })),
      ])
    : [];

  function goToReadinessItem(item: RecordRow) {
    const targetStep = Math.max(0, Number(item.step || 3) - 1);
    setStep(targetStep);
    window.setTimeout(() => document.getElementById(`field-${item.key || item.field_key}`)?.scrollIntoView({ behavior: "smooth", block: "center" }), 80);
  }

  const optionalWarnings: string[] = readiness ? (readiness.optional_warnings || readiness.warnings || []).map((item: unknown) => String(item)) : [];

  return <>

    <div className="page-heading workflow-heading" ref={wizardTopRef}>
      <div>
        <p className="eyebrow">INSPECTION WORKFLOW</p>
        <h1>{isEditingDraft ? "Update inspection draft" : "New inspection wizard"}</h1>
        <span>{privilegedSubmitter ? "Privileged admin submissions can issue certificates immediately after validation." : "Build the inspection in guided steps, then send it for review."}</span>
      </div>
    </div>

    {error && <div className="inline-alert form-error">{error}</div>}
    {message && <div className="inline-alert form-success">{message}</div>}
    {previewUrl && <div className="workflow-result-actions"><a className="secondary-button" href={previewUrl} target="_blank" rel="noreferrer">Open prepared preview</a></div>}
    {issuedCertificate && <div className="workflow-result-actions"><strong>Certificate {issuedCertificate.certificate_number} issued.</strong>{issuedCertificate.pdf_url && <a className="primary-button" href={issuedCertificate.pdf_url} target="_blank" rel="noreferrer">Open issued certificate</a>}{issuedCertificate.verification_url && <a className="secondary-button" href={issuedCertificate.verification_url} target="_blank" rel="noreferrer">Open verification page</a>}{issuedCertificate.qr_url && <a className="secondary-button" href={issuedCertificate.qr_url} target="_blank" rel="noreferrer">Open QR</a>}</div>}

    <div className="workflow-shell">
      <div className="wizard-stepper" role="tablist" aria-label="Inspection wizard steps">
        {wizardSteps.map((wizardStep, index) => {
          const active = index === step;
          const complete = index < step;
          return <button key={wizardStep.id} type="button" className={`wizard-step ${active ? "wizard-step-active" : ""} ${complete ? "wizard-step-complete" : ""}`} onClick={() => setStep(index)}>
            <span>{index + 1}</span>
            <div>
              <strong>{wizardStep.label}</strong>
              <small>{wizardStep.description}</small>
            </div>
          </button>;
        })}
      </div>

      <Panel title={`Step ${step + 1} of ${wizardSteps.length}: ${wizardSteps[step].label}`}>
        <div className="wizard-body">
          {step === 0 && <div className="wizard-grid">
            <label>Client *<select value={form.client_id || ""} onChange={(e) => setField("client_id", e.target.value)} disabled={isEditingDraft}><option value="">Select client</option>{clients.filter((client) => String(client.status || "active") === "active").map((client) => <option key={client.id} value={client.id}>{client.name}{client.short_code ? ` (${client.short_code})` : ""}</option>)}</select></label>
            <label>Equipment *<select value={form.equipment_id || ""} onChange={(e) => setField("equipment_id", e.target.value)}><option value="">Select equipment</option>{filteredEquipment.map((item) => <option key={item.id} value={item.id}>{item.asset_code} - {item.name}</option>)}</select></label>
            <label>Certification category *<select value={form.category_id || ""} onChange={(e) => void loadFields(e.target.value)} disabled={isEditingDraft}><option value="">Select category</option>{categories.map((category) => <option key={category.id} value={category.id}>{category.name}{category.short_code ? ` (${category.short_code})` : ""}</option>)}</select></label>
            {isGeneralCategory && <div className="wide-field inline-alert general-category-warning"><strong>Confirm that no dedicated inspection category exists for this equipment.</strong><span>Use a dedicated inspection category where one exists. This general category is intended only for equipment without an approved dedicated template.</span>{dedicatedSuggestions.length > 0 && <div><b>Possible dedicated categories:</b>{dedicatedSuggestions.map((item) => <button type="button" className="link-button" key={item.id} onClick={() => void loadFields(String(item.id))}>{item.name} already exists. Use the dedicated form.</button>)}</div>}</div>}
          </div>}

          {step === 1 && <div className="wizard-grid">
            <label className="wide-field">{isEditingDraft ? "Allocated certificate reference" : "Generated certificate reference"}
              <input value={displayedReference} readOnly placeholder="Choose client and category first" />
              <span className="form-note">{referenceHint}</span>
            </label>
            <label>Inspection date *<input type="date" value={form.inspection_date || ""} onChange={(e) => setField("inspection_date", e.target.value)} /></label>
            <label>Next due date *<input type="date" value={form.next_due_date || ""} onChange={(e) => setField("next_due_date", e.target.value)} /></label>
            <label className="wide-field">Location<input value={form.location || ""} onChange={(e) => setField("location", e.target.value)} placeholder="Operational Yard / Client Site" /></label>
          </div>}

          {step === 2 && <div className="wizard-grid wizard-grid-sections">
            {fields.length === 0 && sections.length === 0 && <div className="empty-state compact-empty"><AlertCircle size={26} /><strong>No dynamic fields for this category yet</strong><span>Publish category fields and repeatable checklists to collect category-specific inspection data here.</span></div>}
            {fields.map((field) => <label id={`field-${field.field_key}`} key={field.id} className={String(field.field_type) === "textarea" ? "wide-field" : ""}>{field.label}{Number(field.is_required) === 1 ? " *" : ""}{renderField(field)}{field.help_text && <span className="form-note">{field.help_text}</span>}</label>)}
            {sections.map((section) => {
              const sectionKey = String(section.section_key);
              const sectionRows = items[sectionKey] || [];
              const maxRows = section.max_rows ? Number(section.max_rows) : null;
              return <div key={sectionKey} className="wide-field repeatable-section-card">
                <div className="repeatable-section-header">
                  <div>
                    <strong>{section.label}</strong>
                    {section.help_text && <span>{section.help_text}</span>}
                  </div>
                  <button type="button" className="secondary-button" onClick={() => addSectionRow(section)} disabled={maxRows !== null && sectionRows.length >= maxRows}><Plus size={16} />Add row</button>
                </div>
                <div className="table-scroll repeatable-table-wrap">
                  <table className="repeatable-table">
                    <thead>
                      <tr>
                        {(section.columns || []).map((column: RecordRow) => <th key={column.id}>{column.label}{Number(column.is_required) === 1 ? " *" : ""}</th>)}
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      {sectionRows.length === 0 && <tr><td colSpan={(section.columns || []).length + 1}>No rows added yet.</td></tr>}
                      {sectionRows.map((_, rowIndex) => <tr key={`${sectionKey}-${rowIndex}`}>
                        {(section.columns || []).map((column: RecordRow) => <td key={column.id}>{renderSectionCell(sectionKey, rowIndex, column)}</td>)}
                        <td><button type="button" className="link-button danger-link" onClick={() => removeSectionRow(section, rowIndex)}><Trash2 size={14} />Remove</button></td>
                      </tr>)}
                    </tbody>
                  </table>
                </div>
              </div>;
            })}
          </div>}

          {step === 3 && <div className="wizard-grid">
            <label>Evidence photos / PDF <span className="optional-label">Optional</span><input type="file" accept="image/*,.pdf" onChange={(e) => { const file=e.target.files?.[0] || null; setEvidence(file); setUploadStatus((current)=>({...current,evidence:file?`Pending - ${file.name} (${Math.ceil(file.size/1024)} KB)`:"Optional - no new evidence selected"})); }} /><span className="upload-state">{uploadStatus.evidence}</span>{evidence && <button type="button" className="link-button" onClick={() => { setEvidence(null); setUploadStatus((current)=>({...current,evidence:"Optional - pending selection cleared"})); }}>Clear selection</button>}</label>
            <label>Per-inspection signature <span className="optional-label">Optional</span><input type="file" accept="image/png,image/jpeg,image/webp" onChange={(e) => { const file=e.target.files?.[0] || null; setSignature(file); setUploadStatus((current)=>({...current,signature:file?`Pending - ${file.name} (${Math.ceil(file.size/1024)} KB)`:"Optional - no new signature selected"})); }} /><span className="upload-state">{uploadStatus.signature}</span><span className="form-note">Overrides the assigned inspector's active profile signature for this inspection only.</span>{signature && <button type="button" className="link-button" onClick={() => { setSignature(null); setUploadStatus((current)=>({...current,signature:"Optional - pending selection cleared"})); }}>Clear selection</button>}</label>
            {attachments.length > 0 && <div className="wide-field existing-attachments"><strong>Uploaded files</strong>{attachments.map((file) => <div key={file.id} className="attachment-row"><a href={file.download_url} target="_blank" rel="noreferrer"><CheckCircle2 size={15}/><span>{file.file_name}</span><small>{file.attachment_type === "signature" ? "Inspection signature" : "Evidence"} - {Math.ceil(Number(file.file_size || 0)/1024)} KB - {file.mime_type}</small></a><button type="button" className="link-button danger-link" onClick={() => void removeAttachment(Number(file.id))}><Trash2 size={14}/>Remove</button></div>)}</div>}            {reviewComments.length > 0 && <div className="wide-field correction-context"><strong>Reviewer correction context</strong>{reviewComments.map((note) => <p key={note.id}><b>{note.user_name}:</b> {note.comment_text}</p>)}</div>}
            {String(selectedCategory?.template_family || "") !== "endless_round_webbing_sling" && <label className="wide-field">Remarks<textarea value={form.remarks || ""} onChange={(e) => setField("remarks", e.target.value)} placeholder="Inspection notes, observations and remarks" /></label>}
          </div>}

          {step === 4 && <div className="wizard-review-grid">
            <div className="wizard-review-card">
              <strong>Inspection summary</strong>
              <dl>
                {summaryRows.map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}
              </dl>
            </div>
            <div className="wizard-review-card">
              <strong>Submission route</strong>
              <div className="route-card">
                {privilegedSubmitter ? <><CheckCircle2 size={18} /><span>This submission will approve the inspection and generate the certificate immediately if validation passes.</span></> : <><AlertCircle size={18} /><span>This submission will move into reviewer approval before a certificate can be issued.</span></>}
              </div>
              {sourceReference && <div className="form-note form-note-success">Renewal source: {sourceReference}</div>}
              {sections.length > 0 && <div className="form-note">{sections.reduce((count, section) => count + ((items[String(section.section_key)] || []).length), 0)} repeatable checklist row(s) captured.</div>}
            </div>
          </div>}
        </div>

        <div className="wizard-footer">
          <div className="wizard-footer-meta">
            <span>Current step: {currentStepErrors.length > 0 ? `${currentStepErrors.length} item(s) require attention.` : "Complete"}</span>
            {isEditingDraft && readiness && <span className={readiness.ready ? "readiness-ready" : "readiness-blocked"}>Overall certificate readiness: {readiness.ready ? "Ready" : `${readinessItems.length} item(s) require attention`}</span>}
            {isEditingDraft && !readiness && <span className="readiness-checking">Overall certificate readiness: Checking...</span>}
            {lastSavedAt && <span>Last saved: {lastSavedAt}</span>}
            {readinessItems.length > 0 && <div className="readiness-list">{readinessItems.map((item, index) => <div className="readiness-item" key={`${item.key || item.field_key}-${index}`}><div><strong>Step {item.step || 3}: {item.section || "Inspection fields"}</strong><span>{item.label || item.key}</span><small>{item.reason || "This value is required."}</small></div><button type="button" onClick={() => goToReadinessItem(item)}>Go to field</button></div>)}</div>}
            {optionalWarnings.length > 0 && <div className="optional-readiness"><strong>Optional items not provided</strong><ul>{optionalWarnings.map((item, index) => <li key={index}>{String(item)}</li>)}</ul></div>}
          </div>
          <div className="wizard-footer-actions">
            <button className="secondary-button" type="button" onClick={() => void createInspection(false)} disabled={submitting}>{actionState === "saving" ? "Saving draft..." : isEditingDraft ? "Update draft" : "Save draft"}</button>
            {isEditingDraft && <button className="secondary-button" type="button" onClick={resetWizard} disabled={submitting}>Start fresh</button>}
            <button className="secondary-button" type="button" onClick={goBack} disabled={step === 0 || submitting}><ChevronLeft size={16} />Back</button>
            {step === wizardSteps.length - 1 && isEditingDraft && <button className="secondary-button" type="button" onClick={() => void previewCertificate()} disabled={submitting}>{actionState === "previewing" ? "Preparing preview..." : "Preview certificate"}</button>}
            {step < wizardSteps.length - 1 ? <button className="primary-button" type="button" onClick={goNext} disabled={submitting}>Next <ChevronRight size={16} /></button> : <button className="primary-button" type="button" onClick={() => void createInspection(true)} disabled={submitting}>{actionState === "issuing" ? "Validating and issuing..." : privilegedSubmitter ? (isEditingDraft ? "Approve & issue correction" : "Submit & issue certificate") : (isEditingDraft ? "Update & resubmit" : "Submit for review")}</button>}
          </div>
        </div>
      </Panel>
    </div>

    <Panel title="Inspection register" action={<button className="secondary-button" type="button" onClick={() => void load()}><Search size={16} />Refresh</button>}>
      <div className="inspection-register-tools">
        <label><Search size={16}/><input value={searchQuery} onChange={(e)=>setSearchQuery(e.target.value)} placeholder="Search reference, client, equipment or category"/></label>
        <select value={statusFilter} onChange={(e)=>setStatusFilter(e.target.value)}><option value="">All statuses</option><option value="correction">My corrections required</option><option value="draft">Draft</option><option value="submitted">Submitted</option><option value="approved">Approved</option><option value="issued">Issued</option><option value="expired">Expired</option><option value="revoked">Revoked</option></select>
        <span>{rows.filter((row)=>String(row.status)==="correction").length} returned</span>
      </div>
      <div className="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Reference</th>
              <th>Client</th>
              <th>Equipment</th>
              <th>Category</th>
              <th>Inspector</th>
              <th>Date</th>
              <th>Status</th>
              <th>Reviewer notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {filteredRows.map((row) => <tr key={row.id}>
              <td className="mono">{row.reference}</td>
              <td>{row.client_name}</td>
              <td>{row.asset_code} - {row.equipment_name}</td>
              <td>{row.category_name}</td>
              <td>{row.inspector_name}</td>
              <td>{row.inspection_date}</td>
              <td><StatusBadge status={lifecycleLabel(String(row.status))} /></td>
              <td><input className="table-input" value={comment[row.id] || ""} onChange={(e) => setComment((current) => ({ ...current, [row.id]: e.target.value }))} placeholder="Comment or correction note" /></td>
              <td>
                <div className="row-actions wrap-actions">
                  {can(user, "inspections.edit") && <button className="link-button" onClick={() => void addComment(Number(row.id))}>Comment</button>}
                  {can(user, "inspections.edit") && ["draft", "correction"].includes(String(row.status)) && <button className="link-button" onClick={() => void editInspection(Number(row.id))}><Pencil size={14} />{String(row.status) === "correction" ? "Continue Correction" : "Edit"}</button>}
                  {can(user, "inspections.edit") && ["draft", "correction"].includes(String(row.status)) && <button className="link-button" onClick={() => void setStatus(Number(row.id), "submitted")}>{privilegedSubmitter ? "Submit & issue" : "Submit"}</button>}
                  {can(user, "inspections.create") && ["approved", "correction", "issued", "expired", "revoked"].includes(String(row.status)) && <button className="link-button" onClick={() => void cloneInspection(Number(row.id))}><CopyPlus size={14} />Clone / Renew</button>}
                  {can(user, "inspections.review") && String(row.status) === "submitted" && <button className="link-button" onClick={() => void setStatus(Number(row.id), "correction")}>Return</button>}
                  {can(user, "inspections.review") && String(row.status) === "submitted" && <button className="link-button" onClick={() => void setStatus(Number(row.id), "approved")}>Approve</button>}
                  {can(user, "inspections.review") && String(row.status) === "approved" && <button className="link-button" onClick={async () => { await setStatus(Number(row.id), "correction"); await editInspection(Number(row.id)); }}><Pencil size={14} />Edit / Complete Fields</button>}
                  {can(user, "certificates.issue") && ["approved", "issued"].includes(String(row.status)) && <button className="link-button danger-link" onClick={() => void setStatus(Number(row.id), "revoked")}>Revoke</button>}
                </div>
              </td>
            </tr>)}
          </tbody>
        </table>
      </div>
    </Panel>
  </>;
}







