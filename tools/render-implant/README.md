# Titanium implant renderer

Run `node tools/render-implant/render.cjs` from the project root. Uses the existing
Playwright Chromium installation in `/home/codex/tools`; no application dependencies.
Vendored three.js r160 and RoomEnvironment are MIT licensed (license in vendor/).

The fixture is modelled in millimetres with a tapered lathed core, rounded apex,
continuous triangular helical thread, smooth collar and tapered abutment. Physical
titanium uses PMREM room reflections and warm/cool studio lights. Side images use
the teeth's 16.5 × 32.18 mm orthographic frame; the upper view reverses the axis.
Transparent WebP outputs are supersampled 4× and reduced to 160×312 / 64×64.
