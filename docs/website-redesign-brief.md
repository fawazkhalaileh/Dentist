# Website redesign brief

The brief as given by the clinic owner on 2026-10-20, kept verbatim. It governs all public-website work. Ship phase by phase; each phase goes live only after the owner approves it.

## Phase status

- A Audit: done (assets reviewed 2026-10-20; no gallery items have consent recorded yet)
- B Design foundation: shipped 2026-10-20 (35b5fd2)
- C Homepage (+ Meet Dr. Moi page): shipped 2026-10-20 (35b5fd2)
- D Internal pages: shipped 2026-10-20 (7d6135c)
- E Responsive: not started
- F Verification: not started

## Brief


PROJECT: Premium Dental Clinic Website – Complete Frontend Design Redesign
1. Project Objective
Redesign and significantly improve the existing dental clinic website hosted at:
http://20.216.17.199/
Important: This is a frontend design and user-experience project only.
The existing website and connected system must remain intact. We will work on backend functionality, business logic, administration features, appointment workflows, and integrations in a later phase.
For now, focus entirely on creating a beautiful, responsive, premium, modern, and highly usable dental website.
Use realistic dummy data for any content that does not yet exist.
The final website must look like a professionally designed, high-end dental clinic website rather than a generic website template or an administrative dashboard.
2. Existing Project and System Protection
Before making any changes:

1. Inspect the existing project structure, frontend framework, routes, components, backend connections, and existing functionality.
2. Identify which pages and features are already integrated with the current system.
3. Reuse the current frontend architecture where possible.
4. Preserve existing APIs, database connections, authentication, administrative pages, appointment logic, and integrations.
5. Do not remove, replace, or restructure backend functionality.
6. Do not modify database schemas.
7. Do not introduce unnecessary dependencies or duplicate existing functionality.
8. Ensure existing routes and system functionality remain accessible.
9. Use dummy content or mock presentation data where required, clearly separated from real production data.
10. Do not overwrite existing records with dummy content.

The public website should be redesigned while the system remains connected and ready for future development.
Priority: Improve the website design without breaking anything that currently works.
3. Design Inspiration
Use these two websites as visual and UX inspiration:
https://www.prismoralsurgery.com/
https://www.killingsworthfamilydentistry.com/
Study their design approaches, especially:

* Premium visual presentation
* Clean typography and spacious layouts
* Clear navigation
* Beautiful doctor introduction sections
* Dental services presentation
* Appointment call-to-action placement
* Patient-focused information
* Modern photography
* Responsive layouts
* Trust-building elements
* Visual storytelling

Do not copy their design exactly.
Create something more polished, distinctive, memorable, intuitive, and visually cohesive.
The website must establish its own identity through the supplied clinic branding and photography.
4. Uploaded Assets – Critical Requirement
I have already uploaded the following assets to the project:

* Before-and-after patient treatment photos
* Photos of the actual dental clinic
* Photos of the dentist, named Moi
* The official clinic logo

You must inspect all the uploaded assets and actively incorporate them into the design.
Logo
Use the supplied official logo as the primary brand identity.

* Preserve the logo's proportions.
* Do not distort or recreate it.
* Build the website's visual identity around its existing colours.
* Position it professionally in the header.
* Use an appropriate variation for the footer if available.
* Use the logo for the favicon if suitable.

Dentist – Moi
Use the actual uploaded photographs of Moi for the doctor introduction and About page.
The doctor's section should feel personal, premium, and authentic.
Do not substitute generic stock photos of dentists if the uploaded images are suitable.
Use "Dr. Moi" as the temporary display name until the full professional name and qualifications are confirmed.
Do not invent medical credentials, qualifications, professional memberships, or years of experience.
Clinic Photography
Use the uploaded clinic images in:

* Homepage hero or supporting sections
* About the Clinic
* Clinic gallery
* Patient experience sections
* Contact page, where appropriate

The actual clinic photographs should help visitors see the practice before visiting.
Before-and-After Images
Use the uploaded before-and-after photographs to create an excellent smile transformation gallery.
Preserve accurate image pairing and orientation.
Do not modify teeth, treatment results, or patient appearance.
Do not generate fake treatment results.
Only display genuine patient images where publication consent has been confirmed. Otherwise use neutral placeholders until approval.
5. Overall Visual Direction
Build a premium dental website with the following characteristics:
Visual style: Modern, elegant, welcoming, professional, clean, calm, and luxurious without being intimidating.
Avoid a generic hospital website appearance.
Colour Palette
Derive the main colours from the uploaded clinic logo.
Complement these with:

* Warm off-white backgrounds
* Soft ivory or cream
* Subtle neutral greys
* Refined dark text
* Muted brand-colour accents
* Gentle borders and subtle shadows

Avoid excessive bright blue, generic medical gradients, neon colours, or an overly corporate appearance.
Typography
Use modern, highly readable typography.
Choose a refined heading font and a complementary body font.
Requirements:

* Strong heading hierarchy
* Large, confident headlines
* Comfortable reading sizes
* Generous line spacing
* Clear button labels
* Excellent mobile readability

Layout
Use:

* Spacious sections
* Consistent alignment
* Clean image compositions
* Thoughtful asymmetric layouts
* Rounded image corners where appropriate
* Subtle transitions
* Professional cards and content blocks
* Elegant whitespace
* Responsive grids

Avoid overcrowded sections, excessive animations, and repetitive card layouts.
The overall experience should feel custom-designed.
6. Website Navigation
Create a clear, easy-to-use navigation structure:
Home | About Us | Meet Dr. Moi | Treatments | Smile Gallery | Blog | Contact
Include a prominent Book an Appointment button in the header.
On mobile, provide a clean navigation menu with easily accessible appointment and contact actions.
Use a sticky header that remains compact and unobtrusive while scrolling.
Where appropriate, treatment pages should be accessible through a clean dropdown or mega menu.
7. Homepage – Detailed Design
Build a premium homepage with the following sections.
Section 1: Hero
Create an outstanding first impression.
Use a beautiful uploaded clinic or doctor photograph.
Suggested headline:
"Your Smile, Our Passion."
Supporting text:
"Personalised dental care in a welcoming, modern environment. Discover treatment designed around your comfort, confidence, and long-term oral health."
Buttons:

* Book an Appointment
* Explore Our Treatments

Include subtle trust-building visual elements without inventing awards, certifications, ratings, or patient numbers.
The hero must be visually striking on both desktop and mobile.
Section 2: Welcome to Our Clinic
Introduce the dental practice with a warm, patient-centred message.
Use a two-column layout combining:

* Clinic imagery
* A concise clinic introduction
* Key differentiators
* Link to About Us

Use placeholder copy until approved text is supplied.
Section 3: Meet Dr. Moi
Create an elegant and prominent section featuring the actual uploaded photograph of Dr. Moi.
Include:

* Doctor introduction
* Personal approach to dental care
* Focus on patient comfort
* Short biography using placeholder text
* Meet the Doctor button

Do not invent professional qualifications.
The objective is to help patients feel familiar with the dentist before their first appointment.
Section 4: Our Dental Treatments
Create a beautiful visual treatments section.
Use realistic placeholder information for services until the actual treatment list is confirmed.
Suggested demonstration categories:

* General Dentistry
* Cosmetic Dentistry
* Teeth Whitening
* Dental Veneers
* Dental Implants
* Dental Crowns and Bridges
* Teeth Alignment
* Preventive Dental Care

These are sample services, not confirmed clinic offerings.
Each treatment card should include:

* Relevant visual
* Service name
* Short, easy-to-understand description
* Learn More link

Create reusable components so actual service information can later come from the existing system.
Section 5: Smile Transformations
This should be one of the most important visual features.
Build a premium before-and-after gallery using the uploaded images.
Design an interactive comparison slider:

* Before image on one side
* After image on the other
* Draggable comparison handle
* Clear Before and After labels
* Smooth interaction
* Mobile touch support

Also provide a gallery grid displaying treatment transformations.
Allow users to open individual cases in a clean detail view.
Do not make unsupported clinical outcome claims.
Include a subtle results disclaimer where appropriate.
Provide a View All Transformations button.
Section 6: Why Choose Our Clinic?
Create a warm, visually engaging explanation of the patient experience.
Example pillars:

* Personalised Dental Care
* Modern, Comfortable Environment
* Clear Treatment Guidance
* A Patient-First Experience

Use elegant icons or supporting photos.
Avoid generic stock-style marketing language.
Section 7: Inside Our Clinic
Create a beautiful photography section showing the actual practice.
Use a refined image gallery.
Include an optional View Our Clinic button.
This section should help patients feel comfortable and familiar with the space.
Section 8: Patient Testimonials
Create a beautifully styled testimonial carousel.
Use clearly identified dummy testimonials for design purposes only.
Do not present fictional reviews as real patient feedback.
When actual patient reviews become available, the component should be ready to display verified reviews from an approved source.
Section 9: Dental Advice and Blog
Introduce a blog section with three featured articles.
Use realistic educational placeholder topics such as:

* How to Maintain a Healthy Smile
* What to Expect During Your First Dental Visit
* Understanding Your Cosmetic Dentistry Options
* Everyday Habits That Support Oral Health
* A Simple Guide to Teeth Whitening

Create attractive blog cards with:

* Featured image
* Category
* Title
* Brief summary
* Publication date
* Read Article link

Use dummy author information and demo dates, clearly flagged as placeholders.
Section 10: Appointment Call to Action
Create a strong but welcoming conversion section.
Example headline:
"Ready to Feel Confident About Your Smile?"
Text:
"Take the first step towards personalised dental care. Contact our team to discuss your needs."
Buttons:

* Book Your Visit
* Contact Us

Preserve the existing appointment integration if it already works.
Do not implement a new booking backend.
Section 11: Footer
Create a professional footer containing:

* Official logo
* Brief clinic introduction
* Quick links
* Dental services links
* Contact information
* Opening hours
* Social media links
* Location/map link
* Privacy Policy
* Terms and Conditions
* Copyright

Use obvious placeholder contact information until real clinic details are confirmed.
Do not invent a real street address or phone number.
8. Internal Pages
Create consistent designs for the following pages.
About Us
Include:

* Introduction to the clinic
* Clinic mission and philosophy
* Patient care approach
* Actual clinic photographs
* Patient experience highlights
* Link to Meet Dr. Moi
* Appointment CTA

Meet Dr. Moi
Create a premium doctor profile page.
Include:

* Large professional portrait
* Doctor's name
* Introductory biography
* Treatment philosophy
* Professional background placeholders
* Qualifications section awaiting verified information
* Personal approach to patient care
* Appointment CTA

Treatments
Create a treatments overview page with elegant navigation.
Each treatment should have its own detail page or reusable detail template containing:

* Treatment introduction
* Who the treatment may be suitable for
* What the appointment generally involves
* Common patient questions
* Supporting imagery
* Related treatment links
* Appointment CTA

All medical copy must remain general and appropriately qualified until clinically reviewed.
Smile Gallery
Build a dedicated gallery showcasing before-and-after treatments.
Requirements:

* Responsive image grid
* Optional treatment-category filtering
* Interactive before/after comparison
* Fullscreen viewing
* Keyboard-accessible controls
* Mobile touch support
* Consistent treatment-case templates

Use real uploaded, approved images only.
Blog
Create a professional blog system presentation with dummy data.
Required screens:

* Blog overview
* Featured articles
* Category filters
* Individual article page
* Related articles
* Article search if feasible
* Breadcrumb navigation
* Share article links

The structure should be ready for real content integration later.
Create approximately six high-quality sample article layouts.
Clearly identify all sample posts as demo content.
Contact Us
Create a welcoming contact page.
Include:

* Contact details
* Opening hours
* Address placeholder
* Location/map component when valid information becomes available
* Contact form
* Appointment CTA
* Links to social channels

Reuse the existing contact functionality if available.
Otherwise use a clearly marked demo form that does not collect or transmit patient data.
9. Appointment Experience
Appointment booking is important, but the system itself is not being redesigned now.
For this phase:

* Preserve the existing appointment workflow.
* Reuse the existing booking route or interface if one exists.
* Redesign visual components only where safe.
* Keep booking buttons consistent throughout the site.
* Do not create a second booking system.
* Do not change appointment APIs or database logic.
* Do not introduce a payment integration.
* Do not simulate successful real appointment bookings.

If appointment functionality is not yet available, use an attractive frontend demonstration state clearly identified as a preview.
10. Interaction and Animation
Use refined, lightweight animations:

* Smooth hover transitions
* Gentle section entrance effects
* Subtle image reveal effects
* Button interaction feedback
* Smooth mobile menu transitions
* Before/after image comparison
* Tasteful gallery transitions

Respect reduced-motion accessibility preferences.
Animations must enhance the experience, not slow down the website.
Avoid excessive parallax, animated backgrounds, and heavy effects.
11. Mobile Experience
Mobile usability is a critical priority.
Optimise carefully for:

* Modern smartphones
* Tablets
* Desktop screens
* Different viewport sizes

Requirements:

* Easy-to-use mobile navigation
* Readable typography
* Responsive galleries
* Touch-friendly before/after sliders
* Appropriate image cropping
* Large tap targets
* No horizontal overflow
* Fast-loading images
* Clear appointment buttons
* Accessible contact actions

Consider a minimal mobile appointment shortcut if it improves usability without obstructing content.
12. Accessibility and Performance
Design to meet WCAG 2.2 AA accessibility requirements wherever practical.
Include:

* Semantic HTML
* Proper heading hierarchy
* Adequate contrast
* Keyboard navigation
* Visible focus indicators
* Accessible form labels
* Meaningful image alternative text
* Responsive text scaling
* Reduced-motion support

Optimise for strong Core Web Vitals.
Implement:

* Proper image compression
* Modern image formats where supported
* Lazy loading below-the-fold images
* Appropriate image dimensions
* Optimised font loading
* Minimal unnecessary scripts
* Reusable components

Keep the website lightweight, maintainable, and fast.
13. SEO and Content Structure
Prepare the frontend for SEO without implementing a new content-management backend.
Include:

* Proper page titles
* Meta descriptions
* Descriptive routes
* Semantic headings
* Internal linking
* Open Graph metadata
* Article page metadata
* Appropriate image alt text
* Breadcrumbs where useful

Prepare for future LocalBusiness, Dentist, and Article structured data, but do not publish fake business facts, addresses, ratings, qualifications, or testimonials.
Avoid keyword stuffing.
14. Future Content Management Readiness
The website should be designed for future integration with the existing management system.
Structure content into reusable models/components for:

* Treatments
* Doctors
* Gallery cases
* Blogs
* Testimonials
* Clinic information
* Contact details
* Appointments

Keep mock content clearly separated so real records can replace it without rebuilding the frontend.
Do not create or replace an administration system in this phase.
15. What to Avoid
Do not:

* Change or rebuild the backend
* Replace the existing application
* Break current API connections
* Introduce a different authentication system
* Alter existing database schemas
* Create a competing booking system
* Use generic hospital-style templates
* Overuse the colour blue unless supported by the logo
* Use unrelated stock images when genuine clinic photos exist
* Generate artificial patient before/after results
* Invent doctor qualifications
* Publish fabricated patient reviews as genuine
* Display fake clinic contact details as factual
* Overload pages with animations
* Add unnecessary technical complexity
* Remove working features to simplify the redesign

16. Design Quality Expectations
The finished website must:

1. Look like a premium, professionally designed dental clinic website.
2. Use the actual uploaded branding and imagery throughout.
3. Feel welcoming and trustworthy.
4. Make it easy for patients to understand services.
5. Present Dr. Moi beautifully and prominently.
6. Showcase the real clinic.
7. Have an exceptional before-and-after gallery.
8. Include a complete blog design.
9. Make appointment actions easy to find.
10. Perform well on mobile.
11. Maintain a coherent design system.
12. Preserve the current application's functionality.

Pay particular attention to visual detail, spacing, photography, typography, section hierarchy, and consistency.
17. Implementation Approach
Work in this order:
Phase A – Audit
Inspect the current frontend, its routes, uploaded image assets, branding, and existing backend connections.
Phase B – Design Foundation
Create a consistent colour palette, typography system, reusable buttons, navigation, footer, layout containers, cards, and design components.
Phase C – Homepage Redesign
Build the complete homepage with the supplied assets and placeholder text.
Phase D – Internal Pages
Apply the visual system to About, Doctor, Treatments, Gallery, Blog, and Contact.
Phase E – Responsive Refinement
Test layout and usability across desktop, tablet, and mobile.
Phase F – Final Verification
Confirm all existing functionality, navigation, APIs, and backend integrations remain intact.
Fix design inconsistencies, image loading issues, accessibility problems, and responsiveness defects.
18. Final Deliverable
Deliver a completely redesigned, production-quality frontend experience within the existing project.
Use the current application and system as the foundation.
Keep all unconfirmed information as replaceable demo content.
Provide a short final report identifying:

* Pages redesigned
* Uploaded assets incorporated
* UI components added
* Existing backend functions preserved
* Placeholder content still requiring approval
* Any missing assets
* Any integration issues identified but deliberately left unchanged
* Any recommendations for the later system-development phase

Final instruction: Prioritise design quality, authenticity, user experience, and consistency. This is a dental clinic website, not a management dashboard. Make it significantly better than the reference websites while preserving the existing system.

need to ship phase by phase and make sure this is saved in memory start by working on the design etc and let me know when ready to ship