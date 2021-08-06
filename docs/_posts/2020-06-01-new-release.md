---
date: 2020-06-01
---

# New Release: 1.9.0

This release moves to iframes for all digital asset display. This unifies asset display between the Elevator site and embedded assets, simplifying the codebase dramatically and providing sandboxing for the digital asset display code. This resolves a number of weird state issues that could occur when mixing and matching different asset types on a single page.

This change also alters the max sizes for embedded assets. The vertical height of an asset is now constrained to 70% of the viewport (browser) height. Generally, this means assets will be a little bigger by default. This paves the way for fully fluid layouts in the future.


