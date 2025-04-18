**CSS Optimization Notes for `src/assets/css/style.css`**

**1. Component Duplication:**
*   `.card` and `.item-card`: These selectors appear to style the same visual component and have almost identical properties across multiple definitions (Lines 272-289, 750-786). They should be merged into a single `.card` definition.
*   `.top-bar`: Defined twice with overlapping properties (Lines 926-940 and 963-976).
*   `.btn-icon`: Defined twice (Lines 322-335 and 1044-1057).
*   `.form-group`: Defined multiple times with overlapping styles (Lines 566-573, 1115-1117, potentially others).
*   `.form-actions`: Defined multiple times (Lines 502-508, 614-619, 1169-1174).
*   `.toggle-sidebar`: Defined twice with different styles (Lines 942-961 and 993-1006).

**2. Selector Redundancy & Overrides:**
*   `.btn`: Defined multiple times with varying properties (Lines 510-522, 535-544, 546-552, 703-728, 1188-1199). Needs a single base definition.
*   `.btn-primary`: Defined multiple times (Lines 510-522, 535-544, 739-747).
*   `.btn-secondary`: Defined multiple times (Lines 510-522, 535-544, 546-552, 829-833, 876-880).
*   `.sidebar-item:hover`: Defined twice (Lines 160-164 and 190-193).
*   `.sidebar-item.active`: Defined twice (Lines 167-172 and 196-199).
*   `.sidebar-item i`: Multiple definitions/overrides (e.g., Lines 202-204, 229-235, 237-239).
*   `.tag`: Base definition (Lines 387-393) and a slightly different one within `.tags-input` (Lines 648-654).
*   `input`, `textarea`, `select`, `label`: Base styles defined in `.form-group` (e.g., Lines 575-598) are re-defined with variations within `.item-form` (e.g., Lines 468-478, 791-797, 805-826, 1119-1142).
*   `.rating-input button` styles: Defined multiple times with overlaps (e.g., Lines 675-700, 836-863).
*   `.tags-input .tag button` styles: Defined multiple times (e.g., Lines 656-667, 836-847, 861-863).

**3. State/Interaction Style Duplication:**
*   Hover states (`:hover`) for buttons, cards, sidebar items are defined multiple times.
*   Focus states (`:focus`) for inputs/textarea/select are defined multiple times.
*   Active states (`.active`) for sidebar items are defined multiple times.

**4. Media Query Duplication:**
*   `@media (max-width: 700px)` for `.item-form`: Block duplicated (Lines 451-458 and 460-466).
*   `@media (max-width: 500px)` for `.item-form`: Block duplicated (Lines 524-533 and 554-563).

**Recommendation:**
Consolidate these styles by creating single, canonical definitions for each component and element type early in the file. Define variants and states immediately after the base styles. Remove redundant definitions, especially those scoped unnecessarily within `.item-form`. Merge duplicated media query blocks.
