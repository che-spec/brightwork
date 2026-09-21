// Cloudflare Pages Function: POST /api/contact
// Emails assessment-form submissions via Resend, entirely inside this
// Cloudflare account. Requires two things set in the Pages project's
// dashboard (Settings -> Environment variables):
//   RESEND_API_KEY     (secret) - from resend.com/api-keys
//   CONTACT_FROM_EMAIL (plain)  - an address on a domain verified in Resend,
//                                 e.g. "Brightwork Website <notifications@brightworkconsult.com>"
// CONTACT_TO_EMAIL is optional (plain var); defaults to contact@brightworkconsult.com.
//
// See contact-resend.html for the form that posts here, and README.md for
// full setup steps.

const REQUIRED_FIELDS = ["name", "email", "phone"];

function isValidEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

export async function onRequestPost({ request, env }) {
  if (!env.RESEND_API_KEY || !env.CONTACT_FROM_EMAIL) {
    return Response.json(
      { success: false, message: "Contact form is not configured yet." },
      { status: 500 }
    );
  }

  let data;
  try {
    data = await request.formData();
  } catch {
    return Response.json({ success: false, message: "Invalid form submission." }, { status: 400 });
  }

  // Honeypot -- real users never fill this in; bots that fill every field will.
  if ((data.get("website_url") || "").toString().trim() !== "") {
    return Response.json({ success: true }); // pretend success, drop silently
  }

  const fields = {
    name: (data.get("name") || "").toString().trim(),
    email: (data.get("email") || "").toString().trim(),
    phone: (data.get("phone") || "").toString().trim(),
    business_name: (data.get("business_name") || "").toString().trim(),
    preferred_contact: (data.get("preferred_contact") || "").toString().trim(),
    best_time: (data.get("best_time") || "").toString().trim(),
    industry: (data.get("industry") || "").toString().trim(),
    annual_revenue: (data.get("annual_revenue") || "").toString().trim(),
    num_employees: (data.get("num_employees") || "").toString().trim(),
    challenges: (data.get("challenges") || "").toString().trim(),
    timeline: (data.get("timeline") || "").toString().trim(),
    additional_notes: (data.get("additional_notes") || "").toString().trim(),
    subscribe_insights: (data.get("subscribe_insights") || "No").toString().trim(),
  };

  for (const field of REQUIRED_FIELDS) {
    if (!fields[field]) {
      return Response.json({ success: false, message: `Missing required field: ${field}` }, { status: 400 });
    }
  }
  if (!isValidEmail(fields.email)) {
    return Response.json({ success: false, message: "Invalid email address." }, { status: 400 });
  }

  const toEmail = env.CONTACT_TO_EMAIL || "contact@brightworkconsult.com";

  const rows = [
    ["Name", fields.name],
    ["Email", fields.email],
    ["Phone", fields.phone],
    ["Business Name", fields.business_name],
    ["Preferred Contact Method", fields.preferred_contact],
    ["Best Time to Reach", fields.best_time],
    ["Industry", fields.industry],
    ["Annual Revenue", fields.annual_revenue],
    ["Number of Employees", fields.num_employees],
    ["Challenges", fields.challenges],
    ["Timeline", fields.timeline],
    ["Additional Notes", fields.additional_notes],
    ["Subscribe to Insights", fields.subscribe_insights],
  ].filter(([, value]) => value);

  const htmlBody = `
    <h2>New Operations Assessment Submission</h2>
    <table cellpadding="6" cellspacing="0">
      ${rows.map(([label, value]) =>
        `<tr><td valign="top"><strong>${escapeHtml(label)}</strong></td><td valign="top">${escapeHtml(value).replace(/\n/g, "<br>")}</td></tr>`
      ).join("")}
    </table>
  `;
  const textBody = rows.map(([label, value]) => `${label}: ${value}`).join("\n");

  const resendResponse = await fetch("https://api.resend.com/emails", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${env.RESEND_API_KEY}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      from: env.CONTACT_FROM_EMAIL,
      to: [toEmail],
      reply_to: fields.email,
      subject: `New Free Operations Assessment — ${fields.name}`,
      html: htmlBody,
      text: textBody,
    }),
  });

  if (!resendResponse.ok) {
    const errorText = await resendResponse.text().catch(() => "");
    console.error("Resend API error:", resendResponse.status, errorText);
    return Response.json({ success: false, message: "Could not send your message. Please try again." }, { status: 502 });
  }

  return Response.json({ success: true });
}
