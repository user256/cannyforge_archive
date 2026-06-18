# CannyForge Design System

## 1. Visual Theme & Atmosphere

CannyForge’s design language combines calm product structure with a precise forge-inspired accent. The system is violet-led, spacious, and premium, with warm gold used sparingly as a craft signal: borders, top edges, tags, highlights, recommendation states, and small moments of heat.

The brand should feel capable and warm rather than cold or overly technical. It borrows the confidence of serious software tooling, but keeps the interface approachable through soft lavender backgrounds, rounded surfaces, editorial serif display type, and clear Inter-based product UI.

The visual voice is built around a productive tension: **structured violet software system + controlled gold forge detail**. Violet owns hierarchy, interaction, and cohesion. Gold should never dominate the palette or become a broad gradient theme; it works best as a precise edge, spark, status marker, or recommendation accent.

**Key Characteristics:**
- Violet-first product system with warm gold forge accents
- Soft lavender light mode and layered indigo dark mode
- Editorial serif hero and section headings paired with practical Inter UI text
- Rounded pills, cards, panels, fields, and navigation controls
- White or translucent panels with subtle borders and controlled elevation
- Gold used as restrained craft detail, not a primary brand wash
- Calm, capable, maker-oriented tone: craft, clarity, control
- Modular product cards and tool/workflow language

## 2. Color Palette & Roles

### Light Mode Core
- **Forge Violet** (`#6d4aff`): Primary CTA, product accent, links, focus energy, and key interaction color.
- **Royal Purple** (`#372580`): Secondary heading color, structural text, and supporting copy.
- **Purple Ink** (`#1b143f`): Primary readable text on light backgrounds.
- **Night Purple** (`#08031f`): Deepest dark anchor, used for dark mode and high-contrast emphasis.

### Light Mode Surfaces
- **Soft Lavender** (`#f7f5ff`): Main page background.
- **Lavender Tint** (`#e2dbff`): Toned sections, badges, subtle panels, and grouped surfaces.
- **White** (`#ffffff`): Cards, panels, forms, and content surfaces.
- **Cool Gray Surface** (`#f6f7f9`): Neutral support surface.
- **Border Gray** (`#e5e7eb`): Subtle dividers, card borders, and structure.
- **Gray 300** (`#d1d5db`): More visible form and ring border support.
- **Muted Gray** (`#9ca3af`): Metadata, labels, helper text, and quiet descriptions.

### Dark Mode Core
- **Soft Violet** (`#9a82ff`): Primary luminous dark-mode accent.
- **Forge Violet** (`#6d4aff`): Filled CTA color in dark mode.
- **Violet Deep** (`#544590`): Deep violet support tone.
- **Night Purple** (`#08031f`): Base page background.
- **Night Layer** (`#191131`): Upper dark gradient/background layer.
- **Deep Panel** (`#1b1340`): Primary dark surface.
- **Panel Purple** (`#21194a`): Panel layer.
- **Panel Purple 2** (`#2a225b`): Raised panel layer.
- **Text Lavender** (`#f7f5ff`): Primary text on dark backgrounds.
- **Soft Text Lavender** (`#e2dbff`): Supporting copy on dark backgrounds.
- **Muted Violet** (`#b09dff`): Dark-mode links, section labels, and secondary UI text.

### Supporting Accents
- **Gold Accent** (`#ca8a04`): Forge emphasis, recommendation tags, validation examples, section labels in light mode, and special states.
- **Gold Light** (`#fbbf24`): Card top edge, brighter dark-mode gold detail, and small sparks.
- **Gold Soft** (`rgba(202, 138, 4, 0.18)`): Soft gold highlight backgrounds and status fills.
- **Gold Line Light** (`rgba(202, 138, 4, 0.42)`): Light-mode edge and border accent.
- **Gold Line Dark** (`rgba(251, 191, 36, 0.42)`): Dark-mode edge and border accent.
- **Mint Accent** (`#27ddb1`): Fresh technical support accent.
- **Cyan Accent** (`#51e9fe`): Technical energy, subtle radial atmosphere, and secondary gradient support.

## 3. Typography Rules

### Font Family
- **Primary UI Sans**: `Inter`, with fallbacks: `system-ui, sans-serif`
- **Display Serif**: `Instrument Serif`, with fallbacks: `Georgia, serif`

### Hierarchy

| Role | Font | Size | Weight | Line Height | Letter Spacing | Notes |
|------|------|------|--------|-------------|----------------|-------|
| Hero Display | Instrument Serif | 74px | 400 | 0.93 | -0.05em | Large craft-led hero headline |
| Section Heading | Instrument Serif | 52px | 400 | 0.96 | -0.04em | Editorial section titles |
| Card Title | Inter | 24px | 800 | 1.10–1.15 | -0.04em | Product module headings |
| Body Large | Inter | 20px | 400 | 1.50 | normal | Hero support and major explanatory copy |
| Body | Inter | 16px | 400 | 1.55 | normal | Default interface and paragraph text |
| Nav / Label | Inter | 14px | 400–700 | 1.50 | normal | Navigation, fields, helper labels |
| Badge / Eyebrow | Inter | 12px | 800 | 1.20 | 0.05–0.12em | Uppercase tags and section labels |

### Principles
- **Serif for craft and ambition**: Use Instrument Serif for hero lines and large section statements.
- **Inter for product clarity**: Use Inter for controls, cards, metadata, inputs, navigation, and dense UI.
- **Keep the tone crafted, not decorative**: Serif type should feel editorial and warm without becoming ornate.
- **Use purple-led readability**: On light mode, text should lean purple rather than pure black.
- **Dark mode must stay legible and calm**: Use lavender text and muted violet support rather than harsh white-on-black contrast.

## 4. Component Styling

### Navigation
- Sticky top navigation.
- Light mode background: `rgba(247, 245, 255, 0.78)` with `blur(14px)`.
- Dark mode background: `rgba(8, 3, 31, 0.72)` with `blur(14px)`.
- Border bottom is subtle; gold appears as a quiet inset bottom edge.
- Brand wordmark uses strong Inter weight, tight tracking, and simple lowercase product confidence.
- Links are compact and violet-led.
- Header CTA is a filled violet pill with a subtle gold border/inset detail.

### Buttons

**Primary CTA**
- Background: `#6d4aff`
- Text: `#ffffff`
- Radius: `999px`
- Padding: `12px 20px`
- Weight: `700`
- Border: subtle gold-tinted edge
- Shadow: violet glow/lift with an inset gold underside
- Use for actions like “Start forging.”

**Secondary CTA**
- Background: transparent
- Light text: `#6d4aff`
- Dark text: `#f7f5ff`
- Radius: `999px`
- Treatment: inset violet ring plus subtle inset gold lower edge
- Use for actions like “Explore tools.”

### Pills, Tags, and Badges
- Radius: `999px`
- Padding: approximately `8px 12px` or `10px 14px`
- Use uppercase for badge-style tags with `12px`, `800`, and expanded tracking.
- Gold badges in light mode can use warm cream backgrounds and gold text.
- Dark mode badges can use translucent violet surfaces with warm gold text.
- Use tags to express workflow states: Plan, Build, Ship, Recommended, Forge Accent.

### Cards and Product Modules
- Radius: `24px` for standard cards; `28px` for hero panels.
- Light mode card background: `rgba(255, 255, 255, 0.88)`.
- Dark mode card background: layered panel gradient from `rgba(42, 34, 91, 0.92)` to `rgba(27, 19, 64, 0.98)`.
- Border: subtle neutral/lavender border.
- Top border or top edge can use gold to create the forge accent.
- Cards may include a `3px` gold top strip for product modules.
- Hover lift should be subtle: translate up by around `2px` and increase shadow.

### Hero Panel and Mini Cards
- Hero panels should feel like a dashboard preview or product system sample.
- Use a 2-column mini-card grid on desktop.
- Mini cards use rounded `20px` corners, product tokens, and short labels.
- Light mini cards use white-to-lavender gradients.
- Dark mini cards use translucent white surfaces and gold-tinted top borders.

### Forms
- Fields are rounded, conventional, and calm.
- Radius: `16px`
- Padding: `14px 16px`
- Light mode field background: `rgba(255,255,255,0.92)`
- Dark mode field background: `rgba(255,255,255,0.04)`
- Focus state uses a violet ring: `0 0 0 4px rgba(109, 74, 255, 0.10–0.14)`.
- Accent/error example uses a gold-toned ring and border.
- Labels are `14px`, bold, and violet/lavender depending on mode.

## 5. Layout Principles

### Spacing System
- Base unit: `4px`
- Practical scale: `8px`, `12px`, `14px`, `16px`, `18px`, `20px`, `22px`, `24px`, `28px`, `32px`, `48px`, `52px`, `58px`, `80px`

### Grid & Container
- Main container max-width: `1200px`
- Page sections use `58px 32px` padding.
- Hero uses `80px 32px 52px` padding.
- Desktop hero grid: `1.08fr 0.92fr`, with `28px` gap.
- Card grids use `repeat(auto-fit, minmax(280px, 1fr))`.
- Color swatches use `repeat(auto-fit, minmax(170px, 1fr))`.
- Form grids use `repeat(auto-fit, minmax(260px, 1fr))`.

### Whitespace Philosophy
- Keep the system spacious enough to feel premium.
- Product modules can be dense, but each card should have enough internal padding to remain calm.
- Let large serif headings create atmosphere; keep UI modules practical.
- Use repeated dividers and section rhythm to make the design system easy to scan.

### Border Radius Scale
- Small: `8px` for inner decorative elements.
- Standard: `12px` for compact surfaces.
- Comfortable: `16px` for fields and medium controls.
- Card: `20px–24px` for cards and mini cards.
- Hero panel: `28px`.
- Pill: `999px`.

## 6. Depth & Elevation

| Level | Treatment | Light Mode | Dark Mode | Use |
|-------|-----------|------------|-----------|-----|
| Flat | Background only | Lavender page bands | Indigo/night bands | Main sections |
| Surface | Border + soft shadow | `0 14px 34px rgba(59, 37, 128, 0.08)` | `0 18px 40px rgba(0,0,0,0.28)` | Cards, panels, modules |
| Lifted | Stronger shadow | `0 24px 54px rgba(59, 37, 128, 0.12)` | `0 28px 58px rgba(0,0,0,0.40)` | Featured cards, hover states, premium emphasis |

**Shadow Philosophy:** Elevation should make the product feel polished, not glossy. Violet shadows work in light mode; dark mode should use deeper black shadows. Gold belongs on edges and status details, not as large glowing shadows.

## 7. Light Mode Rules

### Do
- Use `#f7f5ff` as the page atmosphere.
- Use white cards with lavender undertones.
- Keep primary copy in `#1b143f` and secondary copy in `#372580`.
- Use `#6d4aff` for primary CTAs and focus cues.
- Use gold as a top border, badge, subtle inset edge, or recommendation tag.
- Use faint cyan and violet radial gradients for background atmosphere.

### Don’t
- Don’t turn the system into a purple-and-gold gradient brand.
- Don’t overuse gold in large fills.
- Don’t use pure black for primary text unless absolutely necessary.
- Don’t make shadows heavy or overly glossy.
- Don’t make cards sharp-cornered or enterprise-flat.

## 8. Dark Mode Rules

### Do
- Use layered indigo and night-purple backgrounds instead of flat black.
- Use `#f7f5ff` and `#e2dbff` for readable text.
- Use `#9a82ff` as the luminous violet accent and `#6d4aff` for filled CTAs.
- Keep cards softly layered with translucent borders.
- Use gold as a precise top edge, badge text, or status detail.
- Preserve the same typography and component rhythm as light mode.

### Don’t
- Don’t make dark mode feel like a generic hacker dashboard.
- Don’t over-brighten borders or text.
- Don’t use constant gold glow.
- Don’t flatten all panels into one dark surface.
- Don’t let mint or cyan compete with violet for structure.

## 9. Responsive Behavior

### Breakpoints
| Name | Width | Key Changes |
|------|-------|-------------|
| Mobile / Tablet | ≤900px | Hide nav links, stack hero into one column, reduce hero top padding |
| Desktop | >900px | Full nav links, two-column hero, full grid behavior |

### Collapsing Strategy
- Hide `.nav-links` at `max-width: 900px`.
- Hero switches from two columns to one column.
- Hero heading reduces from `74px` to `56px`.
- Section heading reduces from `52px` to `42px`.
- Cards, color grids, forms, and elevation grids rely on `auto-fit` responsive behavior.
- Pills and CTAs stay touch-friendly and fully rounded.

## 10. Copy and Tone

### Voice
CannyForge should sound like a calm product workshop: capable, clear, craft-led, and focused. Copy should imply forward motion without hype.

### Useful Phrases
- Forge better products
- Calm, capable tools
- Craft by default
- Structured and sharp
- Craft, clarity, control
- Shape, build, ship
- Product momentum
- Serious tooling without harsh enterprise styling
- Premium craft over noisy maker branding

### Avoid
- Overly aggressive maker language
- Cold enterprise jargon
- Security/privacy framing that belongs to the original Proton inspiration
- Generic SaaS claims without craft/product specificity

## 11. Agent Prompt Guide

### Quick Color Reference
- Forge Violet: `#6d4aff`
- Soft Violet: `#9a82ff`
- Purple Ink: `#1b143f`
- Royal Purple: `#372580`
- Night Purple: `#08031f`
- Deep Panel: `#1b1340`
- Soft Lavender: `#f7f5ff`
- Lavender Tint: `#e2dbff`
- White: `#ffffff`
- Gold Accent: `#ca8a04`
- Gold Light: `#fbbf24`
- Mint Accent: `#27ddb1`
- Cyan Accent: `#51e9fe`

### Example Component Prompts
- “Create a CannyForge hero with a soft lavender background, a large Instrument Serif headline, Inter body copy, pill-shaped violet CTA, and a controlled gold forge accent.”
- “Design a product module grid with white cards, deep-purple text, 24px radius, subtle violet shadow, and a thin gold top edge.”
- “Build a dark-mode CannyForge section with layered indigo panels, lavender text, violet CTAs, and restrained gold status tags.”
- “Create rounded form controls with Inter labels, violet focus rings, and a gold-toned validation example.”
- “Design a craft-led product dashboard preview with mini cards, violet hierarchy, and gold edge details.”

### Iteration Guide
1. Start with violet as the structural system.
2. Add lavender or indigo surfaces depending on mode.
3. Use Instrument Serif only for large brand/editorial moments.
4. Use Inter for all product and UI clarity.
5. Add gold only as a precise forge accent.
6. Keep cards rounded, modular, and disciplined.
7. Preserve calm capability: craft-led, not noisy.
