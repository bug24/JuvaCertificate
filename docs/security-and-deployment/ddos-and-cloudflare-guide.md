# DDoS And Cloudflare Guide

Application throttling cannot stop volumetric DDoS attacks. Proxy `cert.juvaoil.com` through Cloudflare or an equivalent upstream service.

1. Use Full (Strict) SSL with a valid origin certificate.
2. Enable managed WAF rules available on the selected plan.
3. Apply tight rate/challenge rules to `/api/auth/login.php`, password-reset endpoints, `/api/certificates/verify.php`, public PDF/QR downloads and preview/generation routes.
4. Cache hashed static assets only. Bypass cache for `/api/*`, `/verify/*`, authenticated HTML and PDFs.
5. Challenge abnormal request rates, unsupported methods and oversized bodies.
6. Restrict direct origin access to Cloudflare IP ranges where cPanel permits. Shared-hosting limitations must be accepted explicitly.
7. Configure the same approved proxy CIDRs in `trusted_proxies`; never trust forwarded headers from arbitrary clients.
8. Monitor Cloudflare events, 429 rates, failed logins and verification spikes.

Cloudflare plan capabilities vary; confirm current WAF and rate-limiting allowances before go-live.

