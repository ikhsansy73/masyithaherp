# 13 — Market Comparison: Cloud School ERPs vs Masyithah

Benchmark of the Masyithah design ([01–12](README.md)) against cloud-based school ERP/SIS products
on the market, as requested with **[iSAMS](https://www.isams.com/)** as a primary reference.
Research date: **3 October 2026**. Every product claim below links to its source; our-design claims
trace to docs 01–12.

---

## 1. Purpose & method

- **Question**: how does our self-hosted, Laravel/Filament school finance-and-ERP design compare to
  what schools can buy today — feature depth, finance/accounting rigor, and UI/UX?
- **Method**: vendor sites and published module pages for named products; review sites (GetApp,
  EdTech Impact, TrustRadius) for balance; Indonesian market scans (SPP/keuangan apps); government
  ecosystem references (ARKAS, Dapodik).
- **Scope note**: products are compared at design level (not hands-on trials). Pricing is described
  qualitatively — most vendors hide it behind quotes.

## 2. Market landscape

| Segment | Examples | Character |
|---|---|---|
| Premium international MIS/SIS | **iSAMS** (IRIS), Veracross, Engage (Double First), Blackbaud Education | Single-database school systems for independent/international schools; portals + mobile apps; some carry real accounting (iSAMS iFinance, Blackbaud's finance/tuition); quote-based premium pricing; UK/US compliance orientation |
| Asian school-ERP SaaS | **Fedena**, Edunext, Entab, Edisapp (Eloit), Skolera, Gradelink | Broad module lists (50+), fee-centric finance, per-student/month pricing; strong in India/Middle East; rarely a true general ledger |
| Open source | **ERPNext** (Education), **GegoK12** (Laravel), Gibbon, RosarioSIS | Self-hosted; ERPNext is the only one with full double-entry accounting built in; GegoK12 is our exact stack (PHP/Laravel/MySQL); academics-focused, weak finance |
| Indonesian school SaaS | **APPSO**, eSchool.ac.id, Sekolahku.id, AdminSekolah, Okelah, SchoolPay, Infra Digital Nusantara | SPP tagihan & pembayaran specialists + all-in-one school apps; mobile-app-first for parents; WhatsApp/SMS comms; local payment channels; **shallow finance (cash-book style)** |
| Indonesian integrated ERP | **HashMicro** (school + accounting + HRM + inventory as separate modules) | The local "full-ERP" approach: school module bolted onto a business ERP suite — real accounting, but generic and priced accordingly |

**Government ecosystem sidebar (not competitors, but required context for any Indonesian school):**

- **ARKAS 3.0** — the official Kemendikbud application where schools input their RKAS and report
  BOS realization/SPJ ([overview](https://pdfs.semanticscholar.org), [tutorial refs](https://www.youtube.com)). Any school ERP
  that wants to help a BOS-recipient school must at least export ARKAS-compatible figures.
- **Dapodik** — the national student/teacher data registry; market products advertise sync, and
  double entry of data into Dapodik is a real pain point for schools.

## 3. Product profiles

### 3.1 iSAMS — primary benchmark

[isams.com](https://www.isams.com/) · cloud MIS/SIS for independent & international schools
(1,800+ schools, part of IRIS). Modular: MIS core, Admissions, Data & Analytics (iSAMS Central),
Communication, Compliance/safeguarding, Wellbeing, **Finance**, **HR & Payroll**, **Payments**,
portals (parent/student/teacher) and [mobile apps](https://www.isams.com/).

**Finance depth — the reason it's a fair benchmark.** [iFinance](https://www.isams.com/platform/accounting-software-schools/)
is accounting-grade: a "collaborative general ledger" with consolidation and drill-down to
transaction level; **automated depreciation journals**; deferred income handling ("no monthly
manual journals"); **bank reconciliation with bank file uploads and live bank feeds**; timestamped
audit trail; multi-currency; UK VAT rules; **group consolidation across entities/currencies**;
fixed-asset register; stock management; expenses capture; **paperless PO/invoice approval workflow**.
[Fee Billing Manager](https://www.isams.com/platform/modules/feebilling/) generates recurring/split
bills and syncs invoice amendments to the sales/nominal ledgers.
[Payments](https://www.isams.com/payments/) gives parents online settlement of outstanding invoices
via portal, with [Stripe / TransferMate / Flywire](https://www.isams.com/platform/accounting-software-schools/) partners.
HR Manager centralises staff records with self-service ([Sage review](https://www.sage.com.sg/post/software-system-review-isams));
LMS integrations include Canvas, Toddle, Schoolbox, MS Teams, Google Classroom.

**Balance (from reviews):** support complaints ("atrocious service", unresponsive account manager —
[GetApp](https://www.getapp.ae)), email-system unreliability reported since onboarding
([EdTech Impact](https://edtechimpact.com)), the critique that "the basic functionality is there"
and it can feel like "a CRM for schools" ([GetApp](https://www.getapp.ae)), and opaque/expensive
quote-based pricing positioned far above budget all-in-one systems
([GetApp UK](https://www.getapp.co.uk)). Strengths reviewers confirm: single database, REST APIs,
real-time data and quick reports ([SoftwareWorld](https://www.softwareworld.co)).

**Indonesian gap**: no Kurikulum Merdeka rapor (CP→TP, fase, BB/MB/BSH/SB), no BOS/ARKAS or
Dapodik support, no Bahasa Indonesia school-report formats. Payroll is UK-centric (pensions/NI),
not BPJS/PPh 21.

### 3.2 Premium international MIS cluster — Veracross / Engage / Blackbaud

Same cluster as iSAMS ("others like it"): single-database SIS with admissions, tuition/fee billing,
parent & student portals, mobile apps, and US/UK compliance depth; sold to independent schools on
quote-based pricing. Shared strengths (maturity, integration ecosystems) and shared weaknesses for
our context: premium cost, Western compliance orientation, and no Indonesian curriculum or
government-reporting localization. (Cluster-level summary; individual profiles on request.)

### 3.3 Fedena (Foradian) — Asian school-ERP SaaS

[fedena.com](https://fedena.com) — 50+ modules: admissions, student info, exams, attendance,
timetable, **fee collection & fee reports**, HR, **payroll with payroll groups**, library, hostel,
transport, inventory. Role-based logins, lowest-TCO marketing. **Key contrast**: finance is fee
management plus "accounting reports" — there is no chart-of-accounts-driven general ledger,
trial balance, or balance sheet in the core product. Compare its positioning as Education Finance
software on review sites (G2 categories) with a real ERP's GL.

### 3.4 ERPNext (Education domain) — open-source full ERP

[frappe.io ERPNext accounting guide](https://frappe.io/erpnext/erp-guide/accounting-system) — a
full ERP whose Education domain covers admissions, student groups, attendance, **fees**, assessment
plans, courses — and whose **accounting app is real double-entry**: chart of accounts, journal
entries, trial balance, ledger reports. The closest open-source analog to Masyithah's ambition.
Weaknesses for our context: school-agnostic (no Kurikulum Merdeka rapor, no BOS fund dimension,
no kwitansi/terbilang), Frappe/Python stack (not our team's), and heavy configuration to reshape
its generic accounting into Indonesian school statements.

### 3.5 GegoK12 — open-source, our exact stack

[gegok12.com](https://gegok12.com/) — MIT-licensed Laravel/MySQL school system: free core with 26
modules (students/admissions, class & subjects, homework, lesson plans, attendance, staff/leave,
SMS/push, notice board, library, "account management — school income and expenses"), paid add-ons
for fee management ($100), exams/report cards ($100), timetable ($200), transport, inventory, and
payroll/leave as Pro. Free Android parent & teacher apps. **Zero Indonesian localization** —
no Kurikulum Merdeka, no Rupiah-aware formatting mentioned, no BOS/Dapodik. Its "account
management" is basic income/expense tracking, not a GL. This is the clearest evidence that
"open-source Laravel school admin" exists but stops well short of our finance depth.

### 3.6 Classter — modern SaaS SIS

[Classter's own comparison piece](https://www.classter.com/blog/general/compare-the-top-school-erp-software-features-benefits-and-costs/)
(no competitors named, per our fetch) positions itself as modular cloud ERP for K-12/HE: CRM
admissions, academics & LMS, **billing & payments**, HR, library, transport, portals for
parents/students/staff, mobile app, open API, Office 365/Moodle integrations. Industry figures it
cites: subscriptions ~$900–2,000/yr for small institutions up to ~$15,000, plus implementation
$5,000–50,000. Good representative of the modern-SaaS UX bar; finance is billing-centric.

### 3.7 Indonesian school SaaS — APPSO, eSchool, Sekolahku.id, AdminSekolah, SchoolPay, Okelah, Infra Digital

- [APPSO](https://appso.id/): "tagihan SPP, tabungan siswa, absensi, raport digital, LMS, dan
  komunikasi orang tua dalam satu platform" ([site](https://appso.id/),
  [Play Store](https://play.google.com/store/apps/details?id=id.pengembangan.appsoid)).
- [eSchool.ac.id](https://eschool.ac.id/): school management for PAUD–SMA with PPDB online and
  parent/teacher mobile apps ([App Store](https://apps.apple.com/id/app/eschool/id6503661156)).
- [SchoolPay](https://schoolpay.co.id/): flexible SPP/kegiatan/seragam billing.
  [Infra Digital Nusantara](https://infradigital.io/): multi-channel payment network for school
  billing (roundups: [HashMicro](https://www.hashmicro.com), [ScaleOcean]).
- Roundups of the segment: HashMicro's ["10 Rekomendasi Aplikasi Pembayaran SPP"](https://www.hashmicro.com)
  and ScaleOcean's ["13 Aplikasi Pembayaran SPP Sekolah Terbaik di Indonesia"].

**Pattern**: excellent parent-facing UX (mobile apps, WhatsApp/SMS, QRIS/VA payments) and
Indonesian rapor/PPDB workflows — but finance is SPP billing + cash book. None advertise chart of
accounts, general ledger, neraca/laba rugi, tutup buku, fixed-asset depreciation, or fund-based
(BOS vs yayasan) accounting.

### 3.8 HashMicro — Indonesian integrated ERP

[HashMicro](https://www.hashmicro.com) sells a school management system that **integrates with its
separate accounting, HRM (payroll), inventory/asset, CRM and helpdesk systems**
([key-features article](https://www.hashmicro.com)). That gets Indonesian schools real accounting —
as separately-licensed business modules with business-ERP semantics (not school fund accounting,
kwitansi workflows, or Kurikulum Merdeka anything). It is the local proof that "school + real
accounting" is a recognized need, and the closest local architectural alternative to Masyithah.

## 4. Feature comparison matrix

Legend: ✅ designed & documented in our docs (or strong in market product) · ⚠️ partial/possible
with effort · ❌ absent. Masyithah column cites our design docs.

| Capability | Masyithah | iSAMS | Fedena | ERPNext | GegoK12 | Classter | Indonesian SaaS | HashMicro |
|---|---|---|---|---|---|---|---|---|
| Double-entry GL & journals | ✅ [03](03-accounting-core.md) | ✅ iFinance | ❌ fees only | ✅ | ❌ income/expense | ❌ billing | ❌ | ✅ (separate module) |
| Trial balance, Neraca, Laba Rugi, Arus Kas | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Period close (tutup buku) | ✅ | ⚠️ (audit trail yes; close undocumented) | ❌ | ⚠️ | ❌ | ❌ | ❌ | ⚠️ |
| Fund dimension (BOS/Yayasan/Komite) | ✅ | ⚠️ (multi-entity consolidation instead) | ❌ | ⚠️ (cost centers) | ❌ | ❌ | ❌ | ⚠️ (branches) |
| Student billing w/ batch generation & allocation | ✅ [04](04-billing.md) | ✅ Fee Billing | ✅ | ✅ fees | ⚠️ paid add-on | ✅ | ✅ SPP focus | ✅ |
| Online payments (gateway) | ⚠️ reserved, not built | ✅ + intl. rails | ⚠️ | ⚠️ | ⚠️ | ✅ | ✅ QRIS/VA (via [Infra Digital](https://infradigital.io)) | ✅ |
| Bank reconciliation | ❌ gap | ✅ live feeds | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Payroll localized for Indonesia (BPJS, PPh 21) | ✅ [05](05-hr-payroll.md) | ❌ UK-centric | ⚠️ India-centric | ⚠️ config | ⚠️ paid, generic | ❌ | ⚠️ varies | ✅ (HRM module) |
| HR / employee records | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ | ✅ |
| Assets + depreciation journals | ✅ [07](07-assets-inventory.md) | ✅ register + auto journals | ⚠️ inventory | ✅ | ⚠️ paid inventory | ❌ | ❌ | ✅ (asset module) |
| Consumables/stock (ATK) | ✅ | ✅ stock | ✅ inventory | ✅ | ⚠️ paid | ❌ | ❌ | ✅ |
| Students, guardians, PPDB | ✅ [06](06-students-academics.md) | ✅ admissions | ✅ | ✅ | ✅ | ✅ | ✅ PPDB online | ⚠️ |
| Attendance (students) | ✅ daily H/S/I/A | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Curriculum & rapor | ✅ **Kurikulum Merdeka** CP→TP, fase, BB/MB/BSH/SB, rapor workflow | ❌ UK/intl. boards | ⚠️ Indian boards | ⚠️ generic assessment plans | ⚠️ Indian boards (paid) | ⚠️ IB/American | ✅ rapor digital (depth varies) | ❌ |
| Parent portal | ✅ web `/portal` | ✅ + apps | ✅ | ✅ | ✅ + apps | ✅ + apps | ✅ + apps | ✅ |
| Native mobile apps | ❌ | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ | ✅ |
| WhatsApp/SMS/push comms | ❌ (DB notifications only) | ✅ comms module | ✅ SMS | ⚠️ | ✅ SMS/push | ✅ | ✅ core selling point | ⚠️ |
| Multi-school / group | ❌ single school | ✅ groups | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ |
| Dapodik / ARKAS readiness | ⚠️ RKAS data exists; no export | ❌ | ❌ | ❌ | ❌ | ❌ | ⚠️ local vendors claim sync | ⚠️ |
| Audit trail on financials | ✅ activitylog | ✅ | ⚠️ | ✅ | ⚠️ | ⚠️ | ❌ | ✅ |
| Deployment & pricing | self-hosted, **license-free**; our ops | SaaS, quote (expensive) | SaaS, per-student | self-host/cloud, free+hosting | self-host, MIT + paid add-ons | SaaS ~$900–15k + impl. | SaaS, low per-student | SaaS, per-module |

## 5. Finance & accounting depth — the core differentiator

Most school management software is **fee-tracking software**: money in per student, with reports.
Only three products in this comparison do real accounting:

| Aspect | Masyithah | iSAMS iFinance | ERPNext |
|---|---|---|---|
| COA & journals | ✅ COA seed for Indonesian schools; only `JournalPostingService` writes; 17 posting rules | ✅ collaborative GL, drill-down | ✅ generic COA/JE |
| Revenue recognition | accrual: Piutang Siswa at invoice issue; payments clear receivables | deferred income ("no monthly manual journals") | configurable |
| Fund dimension | ✅ BOS/YYS/KOM/UMUM on every journal line → [Laporan Realisasi Dana BOS](03-accounting-core.md) | ⚠️ group consolidation (entities, not funds) | ⚠️ cost centers |
| Depreciation | ✅ monthly runs, idempotent, auto-JE | ✅ automated depreciation journals | ✅ asset module |
| Bank reconciliation | **❌ not designed** | ✅ file upload + live feeds | ✅ |
| Period close | ✅ tutup buku with closing JE, reopen control | ⚠️ undocumented | ⚠️ |
| Statements | ✅ NS, BB, LR, Neraca, Arus Kas (direct), BOS realization; A=K+E asserted | ✅ + BI | ✅ |
| Budgeting | ✅ RKAS with live realization vs journal | ✅ PO/invoice approval workflow; budgets undocumented | ✅ budgets |
| Controls | ✅ void-and-reverse, immutable postings, daily verify command, activitylog | ✅ timestamped audit trail | ✅ |

**Takeaway**: our design matches or exceeds iFinance on school-specific structure (funds, RKAS,
kwitansi, Indonesian COA) at far smaller scope; iFinance wins on maturity features we lack —
bank reconciliation, payment rails, group consolidation, and a procurement/PO workflow.
ERPNext proves the generic-ERP route works but would need exactly the localization layer
(rapor, BOS, kwitansi, BPJS) that is our whole design.

## 6. UI/UX comparison

**Admin console.** Ours: Filament v5 + Tailwind 4 — one `/admin` panel whose navigation
self-hides per role ([10](10-filament-panels.md)), Indonesian labels, six finance dashboard
widgets. Market: Fedena-style products pile 50+ modules into flat menus with dated UIs;
iSAMS/Classter show modern, responsive consoles but with many panels (MIS + iFinance + HR + Central
as separate surfaces). **Our IA is simpler than both clusters at equal feature depth** — the
trade-off is Filament's "productivity admin" aesthetic versus consumer-grade brand polish that
SaaS vendors invest in.

**Parent experience — our biggest UX gap.** Indonesian SaaS and iSAMS/GegoK12 ship native parent
apps (attendance push, tagihan notifications, in-app payments). Ours is a responsive web `/portal`
([10](10-filament-panels.md)) — complete (tagihan, kwitansi, rapor, absensi, jadwal, pengumuman)
but browser-only, and payment recording is bendahara-side by design. Market parents expect an
installable app icon and push notifications.

**Localization.** Ours is Bahasa Indonesia-first with Kurikulum Merdeka rapor as a first-class
workflow ([06](06-students-academics.md)) — the international cluster has neither; Indonesian SaaS
has rapor digital of varying depth, rarely CP/TP-linked with approval workflows.

**Ops experience.** Self-hosted = zero license fees, full data ownership, and freedom to fix/extend
— but patching, backups, SSL and uptime are ours (backups planned in [12](12-roadmap.md) Phase 10),
where SaaS vendors own them (and, per iSAMS reviews, don't always do it well either).

## 7. Pros of our design (vs the market)

1. **Real double-entry accounting with accrual** — GL, tutup buku, and full statements; the
   Indonesian SaaS segment has none of it, and even most international MIS don't (Fedena, Classter).
2. **Fund accounting for BOS/Yayasan/Komite on every journal line** + Laporan Realisasi Dana BOS —
   nobody in the comparison offers fund-dimension accounting; iSAMS substitutes group consolidation.
3. **Kurikulum Merdeka-native academics** — CP→TP, fase A/B/C, BB/MB/BSH/SB, 40/60 formula, rapor
   approval workflow. International MIS: absent. Local SaaS: shallow.
4. **One integrated event-to-journal chain** — billing, payments, payroll, depreciation, assets,
   ATK issuance all post journals through one audited service. HashMicro needs three separately
   licensed modules to approximate this.
5. **Localized payroll** — BPJS kesehatan/tenaga kerja and PPh 21 as data-driven components; iSAMS
   payroll is UK-centric.
6. **Cash-culture fit** — manual kwitansi with terbilang, FIFO allocation, tunggakan aging;
   matches how Indonesian SD bendahara actually work, while staying Midtrans-ready.
7. **Accounting-grade controls** — period locking, void-and-reverse discipline, daily balance
   verification, audit log on financial models.
8. **Deep, row-scoped roles** (7 roles, own-class/own-child scoping) — beyond typical flat
   role toggles in this price class.
9. **Total cost** — self-hosted and license-free vs SaaS subscriptions ($900–15,000+/yr per
   Classter's own industry figures) plus implementation fees; and the code is ours to fix or extend.
10. **Verifiability** — documented schema, posting rules, and a test plan per phase; commercial
    products are black boxes.

## 8. Cons / gaps of our design (vs the market)

1. **No native mobile apps** — market table stakes for parents (and teachers). Responsive web only.
2. **No built-in online payments** — deliberate (manual-first), but QRIS/virtual-account payment
   is a standard Indonesian SaaS selling point; our gateway-ready design is not yet a feature.
3. **No bank reconciliation module** — found during this research; iSAMS (live feeds + file
   upload) and ERPNext both have it. Our `cash_accounts` balance can drift from the real bank
   statement between manual checks.
4. **No WhatsApp/SMS/push fan-out** — tunggakan reminders and pengumuman reach parents only in-app;
   Indonesian parents live on WhatsApp. This is the market's most-used feature.
5. **No procurement/PO workflow** — RKAS is informational; iFinance includes paperless PO/invoice
   approval tied to the ledger.
6. **No Dapodik/ARKAS export adapters** — schools will re-key data into government systems;
   local vendors advertise sync.
7. **Single school only** — no multi-branch/yayasan-group support; every competitor scales to groups.
8. **No LMS/content or LMS integrations** (Canvas/Google Classroom/Teams) — Classter/iSAMS integrate;
   ours is management-only.
9. **No transport/library/hostel/canteen modules** — Fedena/GegoK12/Classter breadth we intentionally
   skip for an SD; still a real absence if requirements grow.
10. **Ops burden is ours** — hosting, backups, updates, security patching.
11. **No AI/analytics extras** — competitors (e.g., Edisapp) market AI features; ours is
    deliberately conventional.
12. **Admin aesthetics** — clean Filament productivity UI, not a branded consumer product look.

## 9. Recommendations / backlog (scheduled as enhancement Phases 11–14 in [12-roadmap.md](12-roadmap.md))

| # | Item | Closes gap | Roadmap | Why / size |
|---|---|---|---|---|
| 1 | **Bank reconciliation page** — import bank statement (CSV), match against `journal_lines` on cash accounts, book differences | Con 3 | **Phase 11** (pullable to right after Phase 2) | Cheapest high-value accounting feature; `bank_statement_lines` table designed in [02](02-database-schema.md) §2, flow in [03](03-accounting-core.md) §7; no change to posting rules |
| 2 | **WhatsApp notification gateway** (Fonnte/Wablas-style) for tunggakan reminders, kwitansi links, pengumuman | Con 4 | **Phase 12** | Highest parent-impact per rupiah; notification-channel abstraction + queued jobs |
| 3 | **PWA for `/portal`** (installable, offline shell), later native wrapper if demand justifies | Con 1 | **Phase 14** | Filament + Laravel PWA tooling; avoids building/maintaining two app stores |
| 4 | **Midtrans QRIS/VA driver** — `PaymentSource` driver + webhook → `PaymentService::record()` | Con 2 | **Phase 12** | Schema already reserved (`payments.reference`); the seam was designed for exactly this |
| 5 | **Dapodik CSV export** (siswa/guru) + **ARKAS-format RKAS export** | Con 6 | **Phase 13** | Export-only adapters, no integrations to maintain; removes re-keying pain |
| 6 | **Procurement-lite** — PO → utang vendor → settlement, posting to 2-1400 (rule exists) | Con 5 | Future backlog | Only if the school wants purchasing control; rule #6 already books utang |
| 7 | Optional modules on demand: transport, library, canteen tabungan siswa (APPSO-style student savings is popular) | Con 9 | Future backlog | Tabungan siswa is the most requested local feature — note for a future phase |
| 8 | Multi-branch/fund-consolidated reporting | Con 7 | Future backlog | Far future; fund dimension already gives 80% of group reporting inside one school |

## 10. Verdict

Against the market, Masyithah is positioned in a real gap: **financially deeper than anything sold
to Indonesian schools** (fund-dimension double-entry, tutup buku, integrated payroll/assets
journals, Kurikulum Merdeka rapor) and **locally deeper than the premium international MIS**
(iSAMS included) that have accounting muscle but no Indonesian curriculum, BOS, or payment-culture
fit. The market's advantages over us are concrete and additive — mobile apps, online payments,
WhatsApp comms, bank reconciliation, integrations — none of them require architectural change to
our design; the backlog in §9 sequences them behind the core build.

---

## Sources

- iSAMS: [home](https://www.isams.com/) · [iFinance accounting](https://www.isams.com/platform/accounting-software-schools/) ·
  [Fee Billing Manager](https://www.isams.com/platform/modules/feebilling/) · [Payments](https://www.isams.com/payments/) ·
  [fee billing blog](https://blog.isams.com/how-to-streamline-school-fee-billing-and-student-data-management) ·
  reviews: [GetApp](https://www.getapp.ae), [GetApp UK](https://www.getapp.co.uk), [EdTech Impact](https://edtechimpact.com),
  [Sage review](https://www.sage.com.sg/post/software-system-review-isams), [SoftwareWorld](https://www.softwareworld.co)
- Classter: [ERP comparison article](https://www.classter.com/blog/general/compare-the-top-school-erp-software-features-benefits-and-costs/)
- Gradelink: [15 Best School Management Software 2026](https://gradelink.com/15-best-school-management-software-for-2026/)
- Fedena: [fedena.com](https://fedena.com)
- ERPNext: [accounting guide](https://frappe.io/erpnext/erp-guide/accounting-system)
- GegoK12: [gegok12.com](https://gegok12.com/)
- Open source roundups: [RosarioSIS top-5](https://www.rosariosis.org/articles/top-5-free-open-source-self-hosted-school-management-systems/)
- HashMicro: [school system key features](https://www.hashmicro.com) · [SPP app roundup](https://www.hashmicro.com)
- Indonesian SaaS: [APPSO](https://appso.id/) · [eSchool](https://eschool.ac.id/) ·
  [SchoolPay](https://schoolpay.co.id/) · [Infra Digital Nusantara](https://infradigital.io/) ·
  [APPSO on Play](https://play.google.com/store/apps/details?id=id.pengembangan.appsoid) ·
  [eSchool on App Store](https://apps.apple.com/id/app/eschool/id6503661156)
- Government ecosystem: ARKAS/RKAS-SPJ references ([academic overview](https://pdfs.semanticscholar.org),
  [governance journal](https://jurnal.balitbangda.lampungprov.go.id))
