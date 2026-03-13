# JewelFAQ — Restructuring Plan
*Last updated: March 2026*

---

## Context

JewelFAQ is a one-person jewellery consultation service run by Colin, a bench jeweller with 30+ years of experience based at 197A Warstone Lane, Birmingham Jewellery Quarter. The main problem to solve is **trust and authority**. The service is solid but the website doesn't show enough visual proof of who Colin is.

---

## What we found across Colin's other sites

| Source | Key proof of authority |
|---|---|
| `diamondsettingschool.com` | Teaches pavé, micro pavé, channel, gypsy/flush, inlay, laser welder. One-on-one intensive courses up to 2 weeks. 22+ years as jeweller and setter. |
| `repairjewellery.co.uk` | **90 years combined team experience**. Advanced pulse laser for prong retipping. Same-day repairs. No outsourcing. |
| `birminghamjewellery.co.uk` | Walk-in repair, same-day if before 11am. Ring resizing, chain repairs, polish, engraving. |
| `remodellingjewellery.co.uk` | Custom design and hand-crafted fine jewellery. Testimonials: "very talented", "very professional". |
| `youtube.com/@jewellerymadebyhand` | YouTube channel — video content, educational, proof of public reach. |
| All sites | Same address: **197A Warstone Lane, Jewellery Quarter, Birmingham B18 6JR** — a prestigious and verifiable location. |

### Photos Colin has available (he confirmed)
- Work at the bench
- Microscope in use
- Settings (pavé, channel, gypsy, flush)
- Restorations
- Everything shown across the other sites above

---

## Problem summary

1. **Three service pages** (individuals, jewellers-3d, students) are the same template — they fragment the site and confuse the journey
2. **Navigation has 6 items** — too many for a one-product service
3. **The form appears on 4 different pages** — no single clear conversion point
4. **Pricing is scattered** — homepage (partial), students page, pricing page — inconsistent per audience
5. **About Colin** mixes bio with "My Other Projects" as a link list — authority material treated as a sidebar
6. **No visual proof of work** — zero photos anywhere, just text claiming expertise
7. **Homepage has 10 sections** — too long before the user finds the form
8. **Contact page is thin** — feels like a dead end, not an invitation to act

---

## Proposed new site structure

### Navigation — from 6 to 4 items
```
How it Works  ·  Pricing  ·  About Colin  ·  Contact
```
- Remove: Individuals, Jewellers & 3D, Students from nav
- Audience selection moves to the form dropdown (already exists)

### Pages — from 7 to 5

| Current | Action | Result |
|---|---|---|
| index.html | Simplify — remove About Colin section, trim to 7 sections | Keep |
| individuals.html | Delete | Remove |
| jewellers-3d.html | Delete | Remove |
| students.html | Delete | Remove |
| pricing.html | No changes needed | Keep |
| about.html | Major redesign — visual authority gallery | Keep |
| contact.html | Minor redesign — more welcoming | Keep |

---

## Page-by-page changes

### 1. index.html — Homepage

**Remove:**
- The "About Colin" section (he has a full page — duplication)
- The final dark CTA section (the form already serves this purpose)

**Keep — reordered:**
1. Hero (unchanged — it's strong)
2. Trust strip (unchanged)
3. Who it's for — **convert to inline accordion**, not links to other pages
4. How it works (unchanged)
5. **NEW: Authority gallery** (see below — 6 photos with captions)
6. Testimonials (unchanged)
7. Pricing summary (unchanged)
8. Form + FAQ (merged at the bottom)

Result: 8 sections instead of 10, form much closer to the top of the page.

---

### 2. about.html — Major redesign (PRIORITY)

**This is the most important page to fix.**

**Current structure:**
1. Hero intro (text)
2. Bio bento grid (2 cards)
3. My Other Projects (link list — WRONG)
4. What JewelFAQ does NOT cover (red card)

**New structure:**
1. Hero intro (unchanged)
2. Bio bento grid (unchanged)
3. **NEW: Credentials strip — 4 numbers in a row**
4. **NEW: "The work behind the advice" — visual gallery**
5. **NEW: Small links row replacing "My Other Projects"**
6. What JewelFAQ does NOT cover (keep, at the end)
7. **NEW: CTA button at the very bottom** — "Send your case"

---

#### Credentials strip — 4 numbers in a row

```
30 years at the bench  |  90 years combined team experience  |  Diamond Setting School founder  |  197A Warstone Lane, Birmingham JQ
```

These are **verifiable facts** pulled from repairjewellery.co.uk and diamondsettingschool.com — not marketing claims.

---

#### "The work behind the advice" — photo gallery

A **3×2 grid** of photos. Each with a short proof caption — not a description, a statement.

| Slot | Photo to use | Caption |
|---|---|---|
| 1 | Bench work / tools close-up | *"At the bench — where the advice comes from"* |
| 2 | Microscope in use | *"Pavé setting under 10x magnification"* |
| 3 | Finished setting (pavé or channel) | *"Channel setting — one of the techniques taught at Diamond Setting School"* |
| 4 | Repair / restoration piece | *"Gold restoration — before and after"* |
| 5 | Diamond Setting School (course or teaching photo) | *"One-to-one teaching at 197A Warstone Lane — intensive courses up to 2 weeks"* |
| 6 | YouTube or workshop scene | *"@jewellerymadebyhand — jewellery education on video"* |

**Placeholder dimensions:** each photo **600×400px** (3:2 ratio)
Grid: 3 columns desktop / 2 columns tablet / 1 column mobile

---

#### What to do with "My Other Projects"

Remove as a section. Replace with a **single discreet text row** at the bottom of the About page:

> *Colin also runs: Diamond Setting School · Jewellery Made by Hand (YouTube) · Birmingham Jewellery · Repair Jewellery · Remodelling Jewellery*

Each word is a link. This keeps SEO value and credibility without taking the user's attention away from JewelFAQ.

---

### 3. individuals.html / jewellers-3d.html / students.html — Delete

The unique content from these pages should be absorbed as follows before deleting:

- **Case examples per audience** → become the "Who it's for" accordion on the homepage
- **Best FAQs** → merged into the main FAQ on the homepage
- **Forms** → removed from these pages; single form stays on homepage and contact

---

### 4. contact.html — Minor redesign

**Change:** Add a short warm intro paragraph above the 3 cards:

> *"The fastest way to get started is to describe your case. If you prefer to ask a quick question first, WhatsApp or email work fine."*

**Remove from public view:** the `[pending]` placeholders in Legal Details (company number, VAT) — they look unfinished. Keep them only in the modals until Colin has the real numbers.

---

## Authority gallery — photo priorities

### What to show — process photos, not product photos

| Priority | Type | Why |
|---|---|---|
| 1 | Microscope + hands working | Unique, specific, proves precision work |
| 2 | Finished setting (pavé or channel) | Shows the level of detail Colin teaches/does |
| 3 | Before/after repair | Shows transformation, proves hands-on skill |
| 4 | Teaching photo at Diamond Setting School | Validates teaching credentials |
| 5 | Bench overview / workshop | Proves real physical location in Jewellery Quarter |
| 6 | YouTube screenshot or recording setup | Shows public credibility |

### What NOT to use
- Generic stock jewellery photos
- Rings on white backgrounds (looks like a shop, not a consultant)
- Too many finished pieces without context (looks like a portfolio, not expertise)

Goal: **Colin working**, not just Colin's results.

---

## Order of implementation

1. **about.html** — credentials strip + photo gallery placeholders (600×400) + remove projects section + small links row + CTA *(start here — highest trust impact)*
2. **index.html** — add authority gallery section + convert "Who it's for" to accordion + remove About Colin section + remove final dark CTA
3. **Merge FAQ content** from the 3 service pages into index.html FAQ
4. **Delete** individuals.html, jewellers-3d.html, students.html
5. **Update navigation** on all remaining pages — remove 3 service links, add "How it works" anchor
6. **contact.html** — add intro paragraph + remove pending placeholders from public card
7. **Replace placeholders** with real photos when Colin selects them

---

## Content Colin needs to provide before going live

- [ ] 6 photos for the authority gallery (see slots above)
- [ ] YouTube subscriber count (optional — show if it's a strong number)
- [ ] Number of students taught at Diamond Setting School (if known)
- [ ] Company registration number
- [ ] VAT number
- [ ] Confirm colin@jewelfaq.com is live and receiving messages

---

## Trust signals already working — keep everywhere

| Signal | Why it works |
|---|---|
| "Send your case free — you only pay if Colin confirms he can help" | Eliminates risk entirely |
| 5-star Google reviews with real names | Social proof — not anonymous |
| "Answer within 24 hours" | Concrete commitment |
| "Not a chatbot" | Strong differentiation in the current AI context |
| 197A Warstone Lane, Jewellery Quarter | Verifiable, prestigious, specific |

---

*Resume point if session ends: start with `about.html`. Build credentials strip + 3×2 photo gallery with 600×400 placeholders + replace "My Other Projects" with small links row + add CTA at bottom.*
