import { useEffect, useState } from "react";
import { Mail, RefreshCw } from "lucide-react";

type ApiResponse<T> = { data: T | null; error: string | null };
type ApiRequest = <T>(path: string, options?: RequestInit, csrf?: string) => Promise<ApiResponse<T>>;

export function MailSettingsPanel({ csrf, request }: { csrf: string; request: ApiRequest }) {
  const [settings, setSettings] = useState<Record<string, any> | null>(null);
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  async function load() {
    const result = await request<any>("/settings/mail.php");
    if (result.data) setSettings(result.data.mail);
    if (result.error) setError(result.error);
  }
  useEffect(() => { void load(); }, []);
  async function test() {
    setMessage(""); setError("");
    const result = await request<any>("/settings/mail-test.php", { method: "POST", body: JSON.stringify({ email }) }, csrf);
    if (result.error) setError(result.error); else setMessage(`Test email sent to ${result.data?.recipient}.`);
  }
  return <section className="panel"><header className="panel-header"><div><h2>SMTP and certificate alerts</h2><p>Credentials are read from the private server configuration and are never displayed here.</p></div><button className="secondary-button" onClick={() => void load()}><RefreshCw size={15} />Refresh</button></header>
    {error && <div className="inline-alert form-error">{error}</div>}{message && <div className="inline-alert form-success">{message}</div>}
    {settings && <><div className="mail-settings-grid">{[
      ["Transport", settings.transport], ["From", settings.from], ["Reply to", settings.reply_to],
      ["SMTP host", settings.smtp_host_configured ? "Configured" : "Missing"], ["SMTP username", settings.smtp_username_configured ? "Configured" : "Missing"],
      ["SMTP password", settings.smtp_password_configured ? "Configured" : "Missing"], ["Encryption", `${settings.smtp_encryption} / ${settings.smtp_port}`],
      ["PHPMailer", settings.phpmailer_available ? "Available" : "Missing"], ["Certificate alerts", settings.notifications_enabled ? "Enabled" : "Disabled"],
    ].map(([label, value]) => <div className="mail-setting" key={label}><small>{label}</small><strong>{String(value)}</strong></div>)}</div>
      <div className="mail-test-form"><label>Test recipient<input type="email" value={email} onChange={event => setEmail(event.target.value)} placeholder="name@example.com" /></label><button className="primary-button" onClick={() => void test()} disabled={!email}><Mail size={16} />Send test email</button></div></>}
  </section>;
}
