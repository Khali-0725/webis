const SECTIONS = [
  {
    title: 'What we collect',
    body: `When you register we collect your name, email address, and optionally your phone number
      and barangay. Providers additionally submit a bio, skills, service areas, a government ID and
      other documents for admin verification, and a payment method (GCash/QR/bank details) clients
      pay into. Bookings record a service location, and payments record a proof-of-transfer
      screenshot and reference number. If you sign in with Google, we receive your name, email, and
      whether Google has verified that email - never your Google password.`,
  },
  {
    title: 'How we use it',
    body: `Strictly to run the marketplace: matching clients with providers, running the booking and
      payment-confirmation workflow, sending account and booking emails, and letting an administrator
      review provider verification documents and moderate reported content. We do not sell your data
      or use it for advertising.`,
  },
  {
    title: 'Who can see what',
    body: `A booking's exact location is only shared with the assigned provider once they accept the
      booking - never before, and never with other providers. Verification documents and payment
      proofs are only ever visible to you, the counterparty on that specific booking, and WEBIS
      administrators - they are never public. Your public provider profile (business name, bio,
      skills, rating) is visible to anyone browsing WEBIS; your email and phone are not.`,
  },
  {
    title: 'Google Sign-In',
    body: `"Continue with Google" is verified server-side against Google's own token endpoint before
      any account is created or signed in. We only ever read the profile fields Google shares (name,
      email, verification status) - WEBIS never sees or stores your Google password, and this sign-in
      method grants us no access to your Gmail, Drive, or any other Google service.`,
  },
  {
    title: 'Other services we rely on',
    body: `WEBIS is hosted on Render and Vercel with a TiDB Cloud database, and uses Pusher Channels
      to deliver realtime updates (new messages, booking and payment status) and Google for sign-in.
      Each only receives the minimum needed to do its job - for example Pusher relays a notice that
      "booking #12 changed," never a message's content or a payment proof.`,
  },
  {
    title: 'Your data, your control',
    body: `You can edit your profile at any time, and delete your own account (with password
      confirmation) from your Profile page - this is a soft delete: your record is removed from
      active use but bookings, payments, and reviews you were party to remain visible to the other
      party and to admins, the same way a paper receipt would. Contact us if you would like a copy of
      your data or have it permanently removed.`,
  },
];

export default function PrivacyPolicyPage() {
  return (
    <div className="mx-auto max-w-3xl px-4 py-14 sm:px-6">
      <p className="text-xs font-medium uppercase tracking-wide text-brand">Privacy</p>
      <h1 className="mt-2 font-display text-3xl font-bold text-navy-800 sm:text-4xl">
        Privacy Policy
      </h1>
      <p className="mt-4 text-sm leading-relaxed text-ink-muted sm:text-base">
        WEBIS is a student capstone project connecting clients with independent service providers in
        Tanza, Cavite. This page explains, in plain language, what information we collect and how we
        use it.
      </p>

      <div className="mt-8 space-y-6">
        {SECTIONS.map((section) => (
          <div key={section.title} className="webis-card p-6">
            <h2 className="font-display text-lg font-semibold text-navy-800">{section.title}</h2>
            <p className="mt-2 text-sm leading-relaxed text-ink-muted">{section.body}</p>
          </div>
        ))}
      </div>

      <p className="mt-8 text-sm text-ink-muted">
        Questions about this policy? Email{' '}
        <a href="mailto:joshuaapilado0725@gmail.com" className="font-medium text-brand hover:text-brand-600">
          joshuaapilado0725@gmail.com
        </a>
        .
      </p>
    </div>
  );
}
