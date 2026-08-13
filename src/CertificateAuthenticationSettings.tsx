import { useEffect, useMemo, useState } from "react";

type UserRow = {
  id: number;
  name: string;
  role?: { name?: string };
  qualification?: string | null;
  job_title?: string | null;
  professional_memberships?: string | null;
  certificate_signing_role?: string | null;
  signature_original_name?: string | null;
  signature_is_active?: number | boolean;
  signature_url?: string | null;
};
type ApiResult<T> = { data: T | null; error: string | null };
type Request = <T>(path: string, options?: RequestInit, csrf?: string) => Promise<ApiResult<T>>;
type ProfessionalProfile = { qualification: string; job_title: string; professional_memberships: string; certificate_signing_role: string };
const emptyProfile: ProfessionalProfile = { qualification: "", job_title: "", professional_memberships: "", certificate_signing_role: "" };

export function CertificateAuthenticationSettings({ users, csrf, apiBase, request }: { users: UserRow[]; csrf: string; apiBase: string; request: Request }) {
  const [selectedId, setSelectedId] = useState("");
  const [signatureFile, setSignatureFile] = useState<File | null>(null);
  const [profile, setProfile] = useState<ProfessionalProfile>(emptyProfile);
  const [branding, setBranding] = useState<Record<string, any>>({});
  const [stampFile, setStampFile] = useState<File | null>(null);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const selected = useMemo(() => users.find((item) => String(item.id) === selectedId), [users, selectedId]);

  useEffect(() => {
    request<{ branding: Record<string, any> }>("/settings/branding.php").then((result) => {
      if (result.data) setBranding(result.data.branding || {});
    });
  }, [request]);

  useEffect(() => {
    setProfile(selected ? {
      qualification: selected.qualification || "",
      job_title: selected.job_title || "",
      professional_memberships: selected.professional_memberships || "",
      certificate_signing_role: selected.certificate_signing_role || "",
    } : emptyProfile);
  }, [selected]);

  function setProfileField(key: keyof ProfessionalProfile, value: string) {
    setProfile((current) => ({ ...current, [key]: value }));
  }

  async function saveProfessionalProfile() {
    if (!selectedId) { setError("Select a staff profile first."); return; }
    const response = await request<{ user: UserRow }>(`/users/user.php?id=${encodeURIComponent(selectedId)}`, { method: "PATCH", body: JSON.stringify(profile) }, csrf);
    if (response.error) { setError(response.error); return; }
    setError("");
    setMessage("Professional certificate profile saved without changing the signature.");
    window.setTimeout(() => window.location.reload(), 500);
  }

  async function signatureAction(action: "upload" | "activate" | "deactivate" | "remove") {
    if (!selectedId) { setError("Select a staff profile first."); return; }
    const body = new FormData();
    body.append("user_id", selectedId);
    body.append("action", action);
    if (signatureFile) body.append("file", signatureFile);
    const response = await fetch(`${apiBase}/users/signature.php`, { method: "POST", body, headers: { "X-CSRF-Token": csrf }, credentials: "include" });
    const payload = await response.json().catch(() => ({ error: "Signature request failed." }));
    if (!response.ok) { setError(payload.error || "Signature request failed."); return; }
    setError(""); setMessage(payload.data?.message || payload.message || "Profile signature updated."); setSignatureFile(null);
    window.setTimeout(() => window.location.reload(), 500);
  }

  async function stampAction(action: "upload_stamp" | "enable_stamp" | "disable_stamp" | "remove_stamp") {
    const body = new FormData(); body.append("action", action); if (stampFile) body.append("file", stampFile);
    const response = await fetch(`${apiBase}/settings/branding.php`, { method: "POST", body, headers: { "X-CSRF-Token": csrf }, credentials: "include" });
    const payload = await response.json().catch(() => ({ error: "Stamp request failed." }));
    if (!response.ok) { setError(payload.error || "Stamp request failed."); return; }
    setError(""); setMessage(payload.data?.message || payload.message || "Company stamp updated."); setStampFile(null);
    const latest = await request<{ branding: Record<string, any> }>("/settings/branding.php");
    if (latest.data) setBranding(latest.data.branding || {});
  }

  return <section className="certificate-auth-settings">
    <div className="settings-heading"><div><strong>Professional certificate identities, signatures and company stamp</strong><span>Maintain reusable staff identity and private signing assets without changing historical certificate snapshots.</span></div></div>
    {error && <div className="inline-alert form-error">{error}</div>}{message && <div className="inline-alert form-success">{message}</div>}
    <div className="certificate-auth-grid">
      <article>
        <h3>Staff professional profile</h3>
        <label>Staff member<select value={selectedId} onChange={(event) => setSelectedId(event.target.value)}><option value="">Select staff</option>{users.map((item) => <option key={item.id} value={item.id}>{item.name} - {item.role?.name || "Staff"}</option>)}</select></label>
        <label>Qualification<input value={profile.qualification} onChange={(event) => setProfileField("qualification", event.target.value)} disabled={!selectedId} placeholder="LEEA, ASNT Level II" /></label>
        <label>Job title<input value={profile.job_title} onChange={(event) => setProfileField("job_title", event.target.value)} disabled={!selectedId} placeholder="Inspection Engineer" /></label>
        <label>Professional memberships<textarea value={profile.professional_memberships} onChange={(event) => setProfileField("professional_memberships", event.target.value)} disabled={!selectedId} placeholder="Relevant professional bodies and grades" /></label>
        <label>Certificate signing role<input value={profile.certificate_signing_role} onChange={(event) => setProfileField("certificate_signing_role", event.target.value)} disabled={!selectedId} placeholder="Inspector / Authenticator" /></label>
        <button className="primary-button" type="button" onClick={() => void saveProfessionalProfile()} disabled={!selectedId}>Save professional profile</button>
      </article>
      <article>
        <h3>Staff profile signature</h3>
        {selected ? <div className="asset-status"><span>{selected.signature_original_name || "No signature uploaded"}</span><strong>{selected.signature_is_active ? "Active" : "Inactive"}</strong>{selected.signature_url && <img src={selected.signature_url} alt={`${selected.name} signature`} />}</div> : <div className="asset-status"><span>Select a staff member to view their signature.</span></div>}
        <label>PNG, JPG/JPEG or WEBP<input type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => setSignatureFile(event.target.files?.[0] || null)} /></label>
        <div className="row-actions"><button className="primary-button" type="button" onClick={() => void signatureAction("upload")} disabled={!selectedId || !signatureFile}>Upload / replace signature</button><button className="secondary-button" type="button" onClick={() => void signatureAction("activate")} disabled={!selectedId}>Activate</button><button className="secondary-button" type="button" onClick={() => void signatureAction("deactivate")} disabled={!selectedId}>Deactivate</button><button className="link-button danger-link" type="button" onClick={() => void signatureAction("remove")} disabled={!selectedId}>Remove</button></div>
      </article>
      <article>
        <h3>JUVA company stamp</h3>
        <div className="asset-status"><span>{branding.company_stamp_original_name || "No company stamp uploaded"}</span><strong>{Number(branding.company_stamp_is_active) === 1 ? "Active" : "Inactive"}</strong>{branding.company_stamp_url && <img src={branding.company_stamp_url} alt="JUVA company stamp" />}</div>
        <label>PNG, JPG or WEBP<input type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => setStampFile(event.target.files?.[0] || null)} /></label>
        <div className="row-actions"><button className="primary-button" type="button" onClick={() => void stampAction("upload_stamp")} disabled={!stampFile}>Upload stamp</button><button className="secondary-button" type="button" onClick={() => void stampAction("enable_stamp")}>Enable</button><button className="secondary-button" type="button" onClick={() => void stampAction("disable_stamp")}>Disable</button><button className="link-button danger-link" type="button" onClick={() => void stampAction("remove_stamp")}>Remove</button></div>
      </article>
    </div>
  </section>;
}