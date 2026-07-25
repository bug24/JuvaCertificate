import { useEffect, useState } from "react";
import { CheckCircle2, ExternalLink, QrCode, Search, X } from "lucide-react";

type VerifyResult = { status: string; certificate: null | Record<string, any> };
type ApiResponse<T> = { data: T | null; error: string | null; validation?: Record<string, string> | null };
type ApiRequest = <T>(path: string, options?: RequestInit, csrf?: string) => Promise<ApiResponse<T>>;

export function EnhancedVerificationPage({ initialRef = '', request }: { initialRef?: string; request: ApiRequest }) {
  const [reference, setReference] = useState(initialRef);
  const [result, setResult] = useState<VerifyResult | null>(null);
  const [error, setError] = useState('');
  const [searched, setSearched] = useState(Boolean(initialRef));

  async function verify(ref = reference) {
    if (!ref.trim()) return;
    setError('');
    setSearched(true);
    const res = await request<VerifyResult>(`/certificates/verify.php?ref=${encodeURIComponent(ref.trim())}`);
    if (res.error) {
      setError(res.error);
      return;
    }
    setResult(res.data);
  }

  useEffect(() => {
    if (initialRef) {
      void verify(initialRef);
    }
  }, [initialRef]);

  const status = result?.status || 'idle';
  const cert = result?.certificate;
  const statusTone = status === 'valid' ? 'valid' : status === 'expired' ? 'warning' : status === 'revoked' || status === 'not_found' ? 'invalid' : 'idle';
  const statusHeading = status === 'valid' ? 'Valid certificate' : status === 'expired' ? 'Expired certificate' : status === 'revoked' ? 'Revoked certificate' : status === 'not_found' ? 'Certificate not found' : 'Verify certificate';

  return <main className="verification-workspace">
    <section className="verify-brand">
      <img src="/logo.png" alt="JUVA Oil" />
      <div>
        <p className="eyebrow">CERTIFICATE VERIFICATION</p>
        <h1>{statusHeading}</h1>
        <span>Search with a verification token, certificate number, or inspection reference.</span>
      </div>
    </section>

    <form className="verify-search-bar" onSubmit={(e) => { e.preventDefault(); void verify(); }}>
      <label>
        <span>Verification token, certificate number, or inspection reference</span>
        <input value={reference} onChange={(e) => setReference(e.target.value)} placeholder="Paste token, certificate number, or inspection reference" />
      </label>
      <button className="primary-button"><Search size={16} />Verify</button>
    </form>

    <section className={`verification-result result-${statusTone}`}>
      {!searched ? <div className="verification-empty-state">
        <Search size={30} />
        <strong>Ready to verify</strong>
        <span>Paste the QR token or the certificate number shown on the document.</span>
      </div> : <>
        <div className="verification-seal">{status === 'valid' ? <CheckCircle2 size={32} /> : <X size={32} />}</div>
        <p>{status === 'idle' ? 'READY' : status.replace('_', ' ').toUpperCase()}</p>
        {cert ? <>
          <h2>{cert.certificate_number}</h2>
          {cert.is_legacy && <span className="legacy-notice">Legacy certificate imported from the old JUVA system. Some inspection detail fields may be preserved as archive data.</span>}
          <dl>
            <div><dt>Client</dt><dd>{cert.client}</dd></div>
            <div><dt>Equipment</dt><dd>{cert.equipment}</dd></div>
            <div><dt>Inspection type</dt><dd>{cert.inspection_type}</dd></div>
            <div><dt>Inspection reference</dt><dd>{cert.inspection_reference}</dd></div>
            <div><dt>Issued</dt><dd>{cert.issue_date}</dd></div>
            <div><dt>Expires</dt><dd>{cert.expiry_date}</dd></div>
            <div><dt>Inspector</dt><dd>{cert.inspector}</dd></div>
            <div><dt>Verification token</dt><dd className="mono verify-token-value">{cert.verification_token}</dd></div>
          </dl>
          <div className="verify-actions">
            {cert.pdf_url && <a className="primary-button" href={cert.pdf_url} target="_blank" rel="noreferrer">Preview / download PDF</a>}
            {cert.verification_url && <a className="secondary-button" href={cert.verification_url} target="_blank" rel="noreferrer"><ExternalLink size={16} />Open verify URL</a>}
            {cert.barcode_url && <a className="secondary-button" href={cert.barcode_url} target="_blank" rel="noreferrer"><QrCode size={16} />View QR</a>}
          </div>
        </> : <span>Check the certificate reference and try again.</span>}
      </>}
    </section>

    {error && <div className="inline-alert form-error">{error}</div>}
  </main>;
}