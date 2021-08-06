---
date: 2020-07-14
---

# New Release: 1.9.2

This release revamps our image rotation detection, and adds support for rotating tiled (zooming) images. This change also ensure that rotation metadata is never written into thumbnail images, which could create problems due to the variance in how browsers handle that metadata.
