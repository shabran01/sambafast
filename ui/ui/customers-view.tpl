{include file="sections/header.tpl"}

<style>
/* ══════════════════════════════════════════════════════════════════════
   Customer detail page — design system.

   Everything is scoped under .sr-cust so no other page or shared Bootstrap
   component can be affected (v2.2.22). The customer profile card carries the
   additional .sr-profile marker (v2.2.21); package cards also use the
   Bootstrap .box-profile class, which is why they are scoped with
   :not(.sr-profile) and the profile rules are written at higher specificity.
   ══════════════════════════════════════════════════════════════════════ */
.connected-devices {
    margin-top: 8px;
}

/* ── v2.2.22 Cards ───────────────────────────────────────────────────── */
.sr-cust .box {
    background: #fff;
    border: 1px solid #e6ebf2;
    border-top: 1px solid #e6ebf2;      /* neutralise Bootstrap's coloured top border */
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 10px 24px -14px rgba(15, 23, 42, .16);
    margin-bottom: 16px;
    overflow: hidden;                    /* clip children to the 14px radius (v2.2.22) */
}
.sr-cust .box.box-primary,
.sr-cust .box.box-danger,
.sr-cust .box.box-success,
.sr-cust .box.box-info,
.sr-cust .box.box-warning,
.sr-cust .box.box-default {
    border-top-color: #e6ebf2;
}
.sr-cust .box-header.with-border {
    background: #fff !important;         /* invisible against the white card, which
                                            is why overflow:visible is safe (v2.2.27) */
    border-bottom: 1px solid #f1f5f9;
    padding: 12px 16px;
}
.sr-cust .box-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
}
.sr-cust .box-body {
    padding: 16px;                       /* deliberately NOT !important: the tab
                                            container keeps its inline padding:0 */
}
.sr-cust .box-body.no-padding {
    padding: 0;
}
.sr-cust .box-footer {
    background: transparent;
    border-top: 1px solid #f1f5f9;
    padding: 8px 14px;
}

/* ── v2.2.21 Customer profile card ───────────────────────────────────── */
.sr-cust .box.sr-profile {
    border-radius: 16px;
    overflow: hidden;                    /* clipped to the 16px radius (v2.2.21) */
    position: relative;
}
.sr-cust .box.sr-profile::before {       /* colour-coded top accent */
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: #dc2626;                 /* red unless Active */
}
.sr-cust .box.sr-profile.sr-profile-on::before {
    background: #2563eb;                 /* Active -> blue */
}
.sr-cust .sr-profile .box-profile {
    padding: 20px 18px 18px;
}
.sr-cust .sr-profile .profile-user-img {
    width: 92px;
    height: 92px;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 0 0 1px #e6ebf2, 0 8px 18px -8px rgba(15, 23, 42, .35);
    margin: 0 auto 12px;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease;
}
.sr-cust .sr-profile .profile-user-img:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 0 0 1px #dbe3ec, 0 14px 26px -10px rgba(15, 23, 42, .45);
}
.sr-cust .sr-profile .profile-username {
    font-size: 19px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -.2px;
    margin: 0 0 14px;
}

/* ── Detail rows — shared by the profile card and the package cards ──── */
/* Replaces Bootstrap's hairline list-group-unbordered table with a flex row:
   uppercase muted label on the left, weight-600 value aligned right. Long
   values wrap instead of breaking the alignment. */
.sr-cust .box-profile .list-group {
    margin-bottom: 0;
}
.sr-cust .box-profile .list-group-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: transparent;
    border: 0;
    border-top: 1px solid #f1f5f9;
    padding: 8px 0;
    font-size: 12.5px;
    color: #334155;
}
.sr-cust .box-profile .list-group-item:first-child {
    border-top: 0;
}
.sr-cust .box-profile .list-group-item > b {
    flex: 0 0 auto;
    margin: 0;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #94a3b8;
    line-height: 1.35;
}
.sr-cust .box-profile .list-group-item > span,
.sr-cust .box-profile .list-group-item > a {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    text-align: right;
    margin-left: auto;
    max-width: 64%;
    word-break: break-word;
}
/* the address row has no label/value pair */
.sr-cust .box-profile .list-group-item > br {
    line-height: 1.5;
}
.sr-cust .sr-profile .list-group-item:first-child {
    border-top: 0;
}
/* ── v2.2.21 Status pill ─────────────────────────────────────────────── */
.sr-cust .sr-status-pill {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.5;
    letter-spacing: .02em;
    white-space: nowrap;
}
.sr-cust .sr-status-on   { background: #dcfce7; color: #15803d; }
.sr-cust .sr-status-off  { background: #fee2e2; color: #b91c1c; }
/* ── v2.2.21 Password / PPPoE password chips ─────────────────────────── */
.sr-cust .sr-secret {
    width: 62%;
    max-width: 62%;
    margin-left: auto;
    padding: 3px 9px;
    background: #f1f5f9;
    border: 1px solid #dbe3ec;
    border-radius: 7px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    text-align: right;
    cursor: pointer;
}
.sr-cust .sr-secret:focus {
    outline: none;
    border-color: #93c5fd;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}

/* ── Value chips ─────────────────────────────────────────────────────── */
/* Shared by the profile card and the package cards, so one colour definition
   drives both instead of two that could drift apart. Declared after the
   generic ".list-group-item > span" rule and with higher specificity, which is
   why the value colour below wins over it. */
.sr-cust .box-profile .list-group-item > span.sr-chip {
    display: inline-block;
    margin-left: auto;
    padding: 2px 9px;
    border: 0;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.6;
    text-align: right;
    white-space: nowrap;
    max-width: 60%;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}
/* Identifiers you would copy out rather than prose */
.sr-cust .box-profile .list-group-item > span.sr-chip-mono {
    background: #f8fafc;
    border: 1px solid #e6ebf2;
    color: #334155;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 12px;
    font-weight: 600;
}
/* min-width:0 above matters specifically because a flex item defaults to
   min-width:auto and would otherwise refuse to shrink, defeating the ellipsis
   on a long email address. */
.sr-cust .box-profile .list-group-item > span.sr-chip-ok    { background: #dcfce7; color: #15803d; }
.sr-cust .box-profile .list-group-item > span.sr-chip-bad   { background: #fee2e2; color: #b91c1c; }
.sr-cust .box-profile .list-group-item > span.sr-chip-info  { background: #dbeafe; color: #1d4ed8; }
.sr-cust .box-profile .list-group-item > span.sr-chip-money { background: #fef3c7; color: #b45309; }
.sr-cust .box-profile .list-group-item > span.sr-chip-mute  { background: #f1f5f9; color: #475569; }

/* Label icon slot: a fixed 14px column so the icons line up down the card, and
   low-contrast so they aid scanning without competing with the values. */
.sr-cust .box-profile .list-group-item > b > i.sr-ico {
    display: inline-block;
    width: 14px;
    margin-right: 2px;
    color: #cbd5e1;
    font-size: 12px;
    text-align: center;
}

/* ── v2.2.21/v2.2.22 Buttons — flat, no 3D bevel ─────────────────────── */
.sr-cust .btn-3d {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    height: 38px;
    padding: 0 14px;
    border: 1px solid transparent;
    border-radius: 10px;
    box-shadow: none;
    font-size: 12.5px;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    transition: background .15s ease, box-shadow .15s ease, transform .1s ease;
}
.sr-cust .btn-3d:active {
    transform: translateY(1px);
    box-shadow: none;
}
.sr-cust .btn-3d:hover {
    opacity: 1;
    color: #fff;
}
.sr-cust .btn-3d > i {
    font-size: 13px;
}
.sr-cust .btn-3d.btn-block + .btn-3d.btn-block {
    margin-top: 0;
}
.sr-cust .btn-3d-danger {
    background: #e11d48;
    border-color: #e11d48;
    color: #fff;
    box-shadow: 0 1px 2px rgba(225, 29, 72, .28);
}
.sr-cust .btn-3d-danger:hover  { background: #be123c; }
.sr-cust .btn-3d-primary {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
    box-shadow: 0 1px 2px rgba(37, 99, 235, .28);
}
.sr-cust .btn-3d-primary:hover { background: #1d4ed8; }
.sr-cust .btn-3d-info {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
    box-shadow: 0 1px 2px rgba(37, 99, 235, .28);
}
.sr-cust .btn-3d-info:hover    { background: #1d4ed8; }
.sr-cust .btn-3d-success {
    background: #16a34a;
    border-color: #16a34a;
    color: #fff;
    box-shadow: 0 1px 2px rgba(22, 163, 74, .28);
}
.sr-cust .btn-3d-success:hover { background: #15803d; }
.sr-cust .btn-3d-warning {
    background: #f59e0b;
    border-color: #f59e0b;
    color: #fff;
    box-shadow: 0 1px 2px rgba(245, 158, 11, .32);
}
.sr-cust .btn-3d-warning:hover { background: #d97706; }
.sr-cust .btn-3d.btn-default {
    background: #f8fafc;
    border-color: #dbe3ec;
    color: #334155;
    box-shadow: none;
}
.sr-cust .btn-3d.btn-default:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.sr-cust .btn-change-router {
    padding: 0 8px;
    font-size: 11.5px;
    letter-spacing: -.2px;
}

/* ── v2.2.22 Tabs — underline style ──────────────────────────────────── */
.sr-cust .nav-tabs {
    background: #fff;
    border-bottom: 1px solid #e6ebf2;
    padding: 0 16px;
}
.sr-cust .nav-tabs > li {
    margin-bottom: -1px;
}
.sr-cust .nav-tabs > li > a {
    background: transparent;
    border: 0;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    color: #64748b;
    font-size: 12.5px;
    font-weight: 600;
    margin-right: 20px;
    padding: 12px 2px;
}
.sr-cust .nav-tabs > li > a:hover,
.sr-cust .nav-tabs > li > a:focus {
    background: transparent;
    border-bottom-color: #cbd5e1;
    color: #1e293b;
}
.sr-cust .nav-tabs > li.active > a,
.sr-cust .nav-tabs > li.active > a:hover,
.sr-cust .nav-tabs > li.active > a:focus {
    background: transparent;
    border: 0;
    border-bottom: 2px solid #2563eb;
    color: #2563eb;
}

/* ── v2.2.22 Tables — hairline dividers, no borders, no zebra ────────── */
.sr-cust .table:not(.usage-history-table) {
    margin-bottom: 0;
}
.sr-cust .table:not(.usage-history-table) > thead > tr > th {
    background: #f8fafc;
    border: 0;
    border-bottom: 1px solid #e6ebf2;
    color: #94a3b8;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .05em;
    padding: 10px 12px;
    text-transform: uppercase;
    white-space: nowrap;
}
.sr-cust .table:not(.usage-history-table) > tbody > tr > td {
    border: 0;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    font-size: 12.5px;
    padding: 10px 12px;
    vertical-align: middle;
}
.sr-cust .table-bordered,
.sr-cust .table-bordered > tbody > tr > td,
.sr-cust .table-bordered > thead > tr > th {
    border-left: 0;
    border-right: 0;
}
.sr-cust .table-striped > tbody > tr:nth-of-type(odd),
.sr-cust .table-striped > tbody > tr:nth-of-type(even) {
    background: transparent;
}
.sr-cust .table-hover > tbody > tr:hover {
    background: #f8fafc;
}

/* ── v2.2.22 Labels and badges — soft rounded pills ──────────────────── */
.sr-cust .label {
    display: inline-block;
    border-radius: 999px;
    padding: 2px 9px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .02em;
    line-height: 1.6;
}
.sr-cust .label-primary { background: #e0e7ff; color: #4338ca; }
.sr-cust .label-success { background: #dcfce7; color: #15803d; }
.sr-cust .label-info    { background: #dbeafe; color: #1d4ed8; }
.sr-cust .label-warning { background: #fef3c7; color: #b45309; }
.sr-cust .label-danger  { background: #fee2e2; color: #b91c1c; }
.sr-cust .label-default { background: #f1f5f9; color: #475569; }
.sr-cust .badge {
    border-radius: 999px;
    font-weight: 700;
}

/* ── v2.2.23 + v2.2.24 Live online indicator ─────────────────────────── */
/* autoload/customer_is_active returns a Bootstrap label whose ONLY content is
   &nbsp;, so it renders as a dot rather than text. The generic .label pill
   rules above would recolour it and pad it into a washed-out stretched oval
   (v2.2.23), so it is targeted through its title attribute — which cannot leak
   onto any other label or badge on the page — and sized to a crisp 13px circle
   (v2.2.24). Padding and font-size are zeroed so it can never stretch back. */
.sr-cust .label[title="online"],
.sr-cust .label[title="error"] {
    display: inline-block;
    width: 13px;
    height: 13px;
    min-width: 13px;
    padding: 0 !important;
    font-size: 0 !important;
    line-height: 13px;
    border-radius: 50%;
    vertical-align: middle;
    margin-left: 6px;                    /* was 5px at 9px (v2.2.24) */
}
.sr-cust .label[title="online"] {
    background: #166534;                 /* dark green */
    box-shadow: 0 0 0 3px rgba(22, 101, 52, .18);
}
.sr-cust .label[title="error"] {
    background: #dc2626;                 /* solid red — router check failed */
    box-shadow: 0 0 0 3px rgba(220, 38, 38, .18);
}

/* ── v2.2.22 Package cards ───────────────────────────────────────────── */
/* The package cards share Bootstrap's .box-profile class name with the profile
   card, so they are scoped with :not(.sr-profile) to avoid a conflict. */
.sr-cust .box-profile:not(.sr-profile) {
    padding: 16px;
}
.sr-cust .box-profile:not(.sr-profile) h4 {
    margin: 0 0 12px;
    font-size: 14.5px;
    font-weight: 700;
    color: #0f172a;
}
/* These rows carry bare label text rather than a <b> element, so the uppercase
   muted label treatment is applied to the row itself and the value span opts
   back out of it. */
.sr-cust .box-profile:not(.sr-profile) .list-group-item {
    color: #94a3b8;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.sr-cust .box-profile:not(.sr-profile) .list-group-item > span {
    color: #1e293b;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0;
    text-transform: none;
}

/* ── v2.2.22 Forms, dropdowns, pagination, alerts, hr, code ──────────── */
.sr-cust .form-control {
    border: 1px solid #dbe3ec;
    border-radius: 10px;
    box-shadow: none;
    font-size: 12.5px;
    height: 38px;
}
.sr-cust .form-control:focus {
    border-color: #93c5fd;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}
.sr-cust .dropdown-menu {
    border: 1px solid #e6ebf2;
    border-radius: 10px;
    box-shadow: 0 12px 32px -12px rgba(15, 23, 42, .28);
    padding: 6px;
}
.sr-cust .pagination > li > a,
.sr-cust .pagination > li > span {
    border: 1px solid #e6ebf2;
    border-radius: 8px;
    color: #475569;
    font-size: 12.5px;
    margin: 0 2px;
}
.sr-cust .pagination > .active > a,
.sr-cust .pagination > .active > a:hover {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}
.sr-cust .alert {
    border: 1px solid transparent;
    border-radius: 10px;
    font-size: 12.5px;
}
.sr-cust hr {
    border-top: 1px solid #e6ebf2;
}
.sr-cust code {
    background: #f1f5f9;
    border-radius: 6px;
    color: #475569;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 12px;
    padding: 2px 6px;
}

/* ── v2.2.25 Action bar ──────────────────────────────────────────────── */
.sr-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    background: #fff;
    border: 1px solid #e6ebf2;
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 10px 24px -16px rgba(15, 23, 42, .2);
    margin: 16px 0;
    padding: 12px;
}
.sr-actions .sr-action {
    flex: 1 1 150px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    height: 40px;
    border: 1px solid transparent;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 700;
    line-height: 1;
    text-decoration: none !important;
    transition: background .15s ease, box-shadow .15s ease, transform .1s ease;
}
.sr-actions .sr-action:active {
    transform: translateY(1px);
}
.sr-actions .sr-action > i {
    font-size: 13px;
}
.sr-action-back {
    background: #f8fafc;
    border-color: #dbe3ec;
    color: #334155;
}
.sr-action-back:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.sr-action-sync {
    background: #f59e0b;
    border-color: #f59e0b;
    box-shadow: 0 1px 2px rgba(245, 158, 11, .32);
    color: #fff;
}
.sr-action-sync:hover {
    background: #d97706;
    color: #fff;
}
.sr-action-msg {
    background: #16a34a;
    border-color: #16a34a;
    box-shadow: 0 1px 2px rgba(22, 163, 74, .28);
    color: #fff;
}
.sr-action-msg:hover {
    background: #15803d;
    color: #fff;
}
.sr-action-login {
    background: #2563eb;
    border-color: #2563eb;
    box-shadow: 0 1px 2px rgba(37, 99, 235, .28);
    color: #fff;
}
.sr-action-login:hover {
    background: #1d4ed8;
    color: #fff;
}

/* ── v2.2.25 Live bandwidth metric tiles ─────────────────────────────── */
.sr-metric {
    display: flex;
    flex-direction: column;
    gap: 3px;
    height: 100%;
    background: #f8fafc;
    border: 1px solid #e6ebf2;
    border-radius: 12px;
    padding: 9px 12px;
}
.sr-metric-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #94a3b8;
}
.sr-metric-value {
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -.4px;
    line-height: 1.1;
    color: #0f172a;
}
.sr-metric-dl  { background: #f0fdf4; border-color: #bbf7d0; }
.sr-metric-dl  .sr-metric-label { color: #16a34a; }
.sr-metric-dl  .sr-metric-value { color: #15803d; }
.sr-metric-ul  { background: #eff6ff; border-color: #bfdbfe; }
.sr-metric-ul  .sr-metric-label { color: #2563eb; }
.sr-metric-ul  .sr-metric-value { color: #1d4ed8; }
.sr-metric-tot { background: #fffbeb; border-color: #fde68a; }
.sr-metric-tot .sr-metric-label { color: #d97706; }
.sr-metric-tot .sr-metric-value { color: #b45309; }

/* ── v2.2.25 Live controls and footer strips ─────────────────────────── */
.sr-cust .sr-live-badge {
    border-radius: 999px;
    font-size: 10.5px;
    padding: 3px 9px;
}
.sr-cust .sr-live-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    padding: 0;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 50%;
    color: #475569;
    font-size: 11px;
    transition: background .15s ease, color .15s ease;
}
.sr-cust .sr-live-toggle:hover {
    background: #e2e8f0;
    color: #1e293b;
}
/* Both footer strips drop their inline grey fill for a transparent background
   with a hairline top border, so they read as part of the card. */
.sr-cust .sr-bandwidth-footer,
.sr-cust .sr-lastupdated {
    background: transparent;
    border-top: 1px solid #f1f5f9;
    padding: 8px 14px;
}
.sr-cust .sr-bandwidth-footer small,
.sr-cust .sr-lastupdated small {
    font-size: 11.5px;
}

/* ── v2.2.27 Actions menu must float above the card ──────────────────── */
/* Bootstrap 3 positions .dropdown-menu INSIDE the card. The rounded-card
   treatment below sets overflow:hidden so child backgrounds are cut to the
   radius, which also clipped the Actions menu away. The menu is right-anchored
   (it was left:0 on a button at the right edge, so its labels ran off the card
   and truncated mid-word) with a 6px offset below the button and aligned 16px
   icon slots, and the labels no longer wrap. */
.sr-cust .box-tools .dropdown-menu {
    left: auto;
    right: 0;
    min-width: 200px;
    margin-top: 6px;
}
.sr-cust .box-tools .dropdown-menu > li > a {
    position: relative;
    border-radius: 6px;
    font-size: 12.5px;
    padding: 8px 10px 8px 34px;
    white-space: nowrap;
}
.sr-cust .box-tools .dropdown-menu > li > a > i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    text-align: center;
}
.sr-cust .box-tools .dropdown-menu > li.divider {
    margin: 5px 0;
}
/* The override is declared after both original rules at matching specificity,
   so it wins without !important. Safe because nothing inside these cards
   actually reaches a corner:
     - .sr-cust .box-header is forced to #fff, so it is invisible on white
     - the two box-footer strips in this template are transparent
     - .list-group items are transparent and sit inside the .box-profile
       padding, so they never touch the radius
   Cards that set overflow:hidden INLINE (the monthly-usage card) keep their own
   clipping, since inline styles beat a non-!important rule. */
.sr-cust .box,
.sr-cust .box.sr-profile {
    overflow: visible;
}
</style>

{* v2.2.22: one .sr-cust container scopes every rule on this page *}
<div class="sr-cust">
<div class="row">
    <div class="col-sm-4 col-md-4">
        <div class="box box-{if $d['status']=='Active'}primary{else}danger{/if} sr-profile {if $d['status']=='Active'}sr-profile-on{else}sr-profile-off{/if}">
            <div class="box-body box-profile sr-profile">
                <div class="box-tools pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-3d btn-3d-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-gear"></i> {Lang::T('Actions')} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="{$_url}customers/sync/{$d['id']}&token={$csrf_token}" onclick="return ask(this, 'This will sync Customer to Mikrotik?')"><i class="fa fa-refresh"></i> {Lang::T('Sync')}</a></li>
                            <li><a href="{$_url}customers/reconnect/{$d['id']}&token={$csrf_token}" onclick="return ask(this, 'This will disconnect and reconnect the customer. Continue?')"><i class="fa fa-power-off"></i> {Lang::T('Reconnect')}</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a href="{$_url}customers/enable/{$d['id']}&token={$csrf_token}" onclick="return ask(this, 'This will enable the customer on Mikrotik router. Continue?')"><i class="fa fa-play"></i> {Lang::T('Enable Customer')}</a></li>
                            <li><a href="{$_url}customers/disable/{$d['id']}&token={$csrf_token}" onclick="return ask(this, 'This will disable the customer on Mikrotik router and disconnect them. Continue?')"><i class="fa fa-stop"></i> {Lang::T('Disable Customer')}</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a href="{$_url}message/send/{$d['id']}&token={$csrf_token}"><i class="fa fa-envelope"></i> {Lang::T('Send Message')}</a></li>
                            <li><a href="{$_url}customers/login/{$d['id']}&token={$csrf_token}" target="_blank"><i class="fa fa-sign-in"></i> {Lang::T('Login as Customer')}</a></li>
                        </ul>
                    </div>
                </div>
                <img class="profile-user-img img-responsive img-circle"
                    onclick="window.location.href = '{$UPLOAD_PATH}{$d['photo']}'"
                    src="{$UPLOAD_PATH}{$d['photo']}.thumb.jpg"
                    onerror="this.src='{$UPLOAD_PATH}/user.default.jpg'" alt="avatar">
                <h3 class="profile-username text-center">{$d['fullname']}</h3>
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b><i class="fa fa-circle sr-ico"></i> {Lang::T('Status')}</b> <span
                            class="pull-right sr-chip {if $d['status'] !='Active'}sr-chip-bad{else}sr-chip-ok{/if}">{Lang::T($d['status'])}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fa fa-user sr-ico"></i> {Lang::T('Username')}</b> <span class="pull-right sr-chip sr-chip-mono">{$d['username']}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fa fa-phone sr-ico"></i> {Lang::T('Phone Number')}</b> <span class="pull-right sr-chip sr-chip-mono">{$d['phonenumber']}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fa fa-envelope-o sr-ico"></i> {Lang::T('Email')}</b> <span class="pull-right sr-chip sr-chip-mono">{$d['email']}</span>
                    </li>
                    <li class="list-group-item">{Lang::nl2br($d['address'])}</li>
                    <li class="list-group-item">
                        <b><i class="fa fa-map-marker sr-ico"></i> {Lang::T('City')}</b> <span class="pull-right">{$d['city']}</span>
                    </li>
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="list-group-item">
                            <b><i class="fa fa-key sr-ico"></i> {Lang::T('Password')}</b> <input type="password" value="{$d['password']}"
                                class="sr-secret"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                                onclick="this.select()">
                        </li>
                    {/if}
                    {if $d['pppoe_username'] != ''}
                        <li class="list-group-item">
                            <b><i class="fa fa-plug sr-ico"></i> PPPOE {Lang::T('Username')}</b> <span class="pull-right sr-chip sr-chip-mono">{$d['pppoe_username']}</span>
                        </li>
                    {/if}
                    {if $d['pppoe_password'] != '' && in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="list-group-item">
                            <b><i class="fa fa-key sr-ico"></i> PPPOE {Lang::T('Password')}</b> <input type="password" value="{$d['pppoe_password']}"
                                class="sr-secret"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                                onclick="this.select()">
                        </li>
                    {/if}
                    {if $d['pppoe_ip'] != ''}
                        <li class="list-group-item">
                            <b><i class="fa fa-globe sr-ico"></i> PPPOE Remote IP</b> <span class="pull-right sr-chip sr-chip-mono">{$d['pppoe_ip']}</span>
                        </li>
                    {/if}
                    <!--Customers Attributes view start -->
                    {if $customFields}
                        {foreach $customFields as $customField}
                            <li class="list-group-item">
                                <b>{$customField.field_name}</b> <span class="pull-right">
                                    {if strpos($customField.field_value, ':0') === false}
                                        {$customField.field_value}
                                    {else}
                                        <b>{Lang::T('Paid')}</b>
                                    {/if}
                                </span>
                            </li>
                        {/foreach}
                    {/if}
                    <!--Customers Attributes view end -->
                    <li class="list-group-item">
                        <b><i class="fa fa-tag sr-ico"></i> {Lang::T('Service Type')}</b> <span class="pull-right sr-chip sr-chip-info">{Lang::T($d['service_type'])}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fa fa-id-card-o sr-ico"></i> {Lang::T('Account Type')}</b> <span class="pull-right sr-chip sr-chip-mono">{Lang::T($d['account_type'])}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fa fa-money sr-ico"></i> {Lang::T('Balance')}</b> <span class="pull-right sr-chip sr-chip-money">{Lang::moneyFormat($d['balance'])}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fa fa-refresh sr-ico"></i> {Lang::T('Auto Renewal')}</b> <span
                            class="pull-right sr-chip {if $d['auto_renewal']}sr-chip-ok{else}sr-chip-bad{/if}">{if $d['auto_renewal']}{Lang::T('Yes')}{else}{Lang::T('No')}{/if}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fa fa-calendar sr-ico"></i> {Lang::T('Created On')}</b> <span
                            class="pull-right">{Lang::dateTimeFormat($d['created_at'])}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fa fa-clock-o sr-ico"></i> {Lang::T('Last Login')}</b> <span
                            class="pull-right">{Lang::dateTimeFormat($d['last_login'])}</span>
                    </li>
                    {if $d['coordinates']}
                        <li class="list-group-item">
                            <b><i class="fa fa-map-marker sr-ico"></i> {Lang::T('Coordinates')}</b> <span class="pull-right">
                                <i class="glyphicon glyphicon-road"></i> <a style="color: black;"
                                    href="https://www.google.com/maps/dir//{$d['coordinates']}/"
                                    target="_blank">{Lang::T('Get Directions')}</a>
                            </span>
                        </li>
                    {/if}
                </ul>
                <div class="row">
                    <div class="col-xs-4">
                        <button type="button" onclick="confirmDelete('{$_url}customers/delete/{$d['id']}&token={$csrf_token}')" 
                            class="btn btn-3d btn-3d-danger btn-sm btn-block"><i class="fa fa-trash"></i> {Lang::T('Delete')}</button>
                    </div>
                    <div class="col-xs-4">
                        <a href="{$_url}customers/edit/{$d['id']}&token={$csrf_token}"
                            class="btn btn-3d btn-3d-primary btn-sm btn-block"><i class="fa fa-pencil"></i> {Lang::T('Edit')}</a>
                    </div>
                    <div class="col-xs-4">
                        <a href="{$_url}customers/change_router/{$d['id']}&token={$csrf_token}" 
                            class="btn btn-3d btn-3d-info btn-sm btn-block btn-change-router">
                            <i class="fa fa-random"></i> {Lang::T('Change Router')}
                        </a>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-xs-12">
                        <a href="{$_url}customers/list" class="btn btn-3d btn-3d btn-default btn-sm btn-block"><i class="fa fa-arrow-left"></i> {Lang::T('Back')}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-8 col-md-8">
        <div class="box box-success">
            <div class="box-body">
                <div class="row">
                    {if $_c['enable_balance'] == 'yes' && $_c['extend_expired']}
                    <div class="col-xs-6 col-sm-3" style="margin-bottom:6px">
                        <a href="{$_url}plan/recharge/{$d['id']}" class="btn btn-3d btn-3d-success btn-block">
                            <i class="fa fa-credit-card"></i> {Lang::T('Recharge Account')}
                        </a>
                    </div>
                    <div class="col-xs-6 col-sm-3" style="margin-bottom:6px">
                        <a href="{$_url}plan/deposit/{$d['id']}" class="btn btn-3d btn-3d-primary btn-block">
                            <i class="fa fa-money"></i> {Lang::T('Add Balance')}
                        </a>
                    </div>
                    {if $_admin['user_type'] == 'SuperAdmin' || $_admin['user_type'] == 'Admin'}
                    <div class="col-xs-6 col-sm-3" style="margin-bottom:6px">
                        <a href="{$_url}plan/deduct/{$d['id']}" class="btn btn-3d btn-3d-danger btn-block">
                            <i class="fa fa-minus-circle"></i> {Lang::T('Subtract Balance')}
                        </a>
                    </div>
                    {/if}
                    <div class="col-xs-6 col-sm-3" style="margin-bottom:6px">
                        <button onclick="extendCustomerPlan('{$d['id']}')" class="btn btn-3d btn-3d-warning btn-block">
                            <i class="fa fa-clock-o"></i> {Lang::T('Extend')}
                        </button>
                    </div>
                    {elseif $_c['enable_balance'] == 'yes'}
                    <div class="col-xs-6 col-sm-4" style="margin-bottom:6px">
                        <a href="{$_url}plan/recharge/{$d['id']}" class="btn btn-3d btn-3d-success btn-block">
                            <i class="fa fa-credit-card"></i> {Lang::T('Recharge Account')}
                        </a>
                    </div>
                    <div class="col-xs-6 col-sm-4" style="margin-bottom:6px">
                        <a href="{$_url}plan/deposit/{$d['id']}" class="btn btn-3d btn-3d-primary btn-block">
                            <i class="fa fa-money"></i> {Lang::T('Add Balance')}
                        </a>
                    </div>
                    {if $_admin['user_type'] == 'SuperAdmin' || $_admin['user_type'] == 'Admin'}
                    <div class="col-xs-12 col-sm-4" style="margin-bottom:6px">
                        <a href="{$_url}plan/deduct/{$d['id']}" class="btn btn-3d btn-3d-danger btn-block">
                            <i class="fa fa-minus-circle"></i> {Lang::T('Subtract Balance')}
                        </a>
                    </div>
                    {/if}
                    {elseif $_c['extend_expired']}
                    <div class="col-xs-6" style="margin-bottom:6px">
                        <a href="{$_url}plan/recharge/{$d['id']}" class="btn btn-3d btn-3d-success btn-block">
                            <i class="fa fa-credit-card"></i> {Lang::T('Recharge Account')}
                        </a>
                    </div>
                    <div class="col-xs-6" style="margin-bottom:6px">
                        <button onclick="extendCustomerPlan('{$d['id']}')" class="btn btn-3d btn-3d-warning btn-block">
                            <i class="fa fa-clock-o"></i> {Lang::T('Extend')}
                        </button>
                    </div>
                    {else}
                    <div class="col-xs-12">
                        <a href="{$_url}plan/recharge/{$d['id']}" class="btn btn-3d btn-3d-success btn-block">
                            <i class="fa fa-credit-card"></i> {Lang::T('Recharge Account')}
                        </a>
                    </div>
                    {/if}
                </div>
            </div>
        </div>
        <div class="box box-info">
            <ul class="nav nav-tabs">
                <li role="presentation" {if $v=='order' }class="active" {/if}><a
                        href="{$_url}customers/view/{$d['id']}/order">{Lang::T('Order History')}</a></li>
                <li role="presentation" {if $v=='activation' }class="active" {/if}><a
                        href="{$_url}customers/view/{$d['id']}/activation">{Lang::T('Activation History')}</a></li>
                <li role="presentation" {if $v=='tickets' }class="active" {/if}><a
                        href="{$_url}customers/view/{$d['id']}/tickets"><i class="fa fa-ticket"></i> Support Tickets</a></li>
                <li role="presentation" {if $v=='smslogs' }class="active" {/if}><a
                        href="{$_url}customers/view/{$d['id']}/smslogs"><i class="fa fa-comments"></i> SMS Logs</a></li>
                <li role="presentation" {if $v=='mklogs' }class="active" {/if}><a
                        href="{$_url}customers/view/{$d['id']}/mklogs"><i class="fa fa-terminal"></i> MT Logs</a></li>
            </ul>
            <div class="box-body" style="padding:0;">
            {if $v=='activation'}
            <div class="table-responsive">
                <table class="table table-bordered table-striped" style="white-space:nowrap;">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>{Lang::T('Plan Name')}</th>
                            <th>{Lang::T('Plan Price')}</th>
                            <th>{Lang::T('Type')}</th>
                            <th>{Lang::T('Created On')}</th>
                            <th>{Lang::T('Expires On')}</th>
                            <th>{Lang::T('Method')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if Lang::arrayCount($activation)}
                            {foreach $activation as $ds}
                                <tr onclick="window.location.href = '{$_url}plan/view/{$ds['id']}'" style="cursor:pointer;">
                                    <td>{if $ds['invoice']}{$ds['invoice']}{else}#{$ds['id']}{/if}</td>
                                    <td>{$ds['plan_name']}</td>
                                    <td>{Lang::moneyFormat($ds['price'])}</td>
                                    <td>{$ds['type']}</td>
                                    <td class="text-success">{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                                    <td class="text-danger">{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                                    <td>{$ds['method']}</td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr><td colspan="7" class="text-center text-muted" style="padding:20px;">{Lang::T('No activation records found.')}</td></tr>
                        {/if}
                    </tbody>
                </table>
            </div>

            {elseif $v=='order'}
            <div class="table-responsive">
                <table class="table table-bordered table-striped" style="white-space:nowrap;">
                    <thead>
                        <tr>
                            <th>{Lang::T('Plan Name')}</th>
                            <th>{Lang::T('Gateway')}</th>
                            <th>{Lang::T('Routers')}</th>
                            <th>{Lang::T('Type')}</th>
                            <th>{Lang::T('Plan Price')}</th>
                            <th>{Lang::T('Created On')}</th>
                            <th>{Lang::T('Expires On')}</th>
                            <th>{Lang::T('Date Done')}</th>
                            <th>{Lang::T('Method')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if Lang::arrayCount($order)}
                            {foreach $order as $ds}
                                <tr>
                                    <td>{$ds['plan_name']}</td>
                                    <td>{$ds['gateway']}</td>
                                    <td>{$ds['routers']}</td>
                                    <td>{$ds['payment_channel']}</td>
                                    <td>{Lang::moneyFormat($ds['price'])}</td>
                                    <td class="text-primary">{Lang::dateTimeFormat($ds['created_date'])}</td>
                                    <td class="text-danger">{Lang::dateTimeFormat($ds['expired_date'])}</td>
                                    <td class="text-success">{if $ds['status']!=1}{Lang::dateTimeFormat($ds['paid_date'])}{/if}</td>
                                    <td>{if $ds['status']==1}{Lang::T('UNPAID')}
                                        {elseif $ds['status']==2}{Lang::T('PAID')}
                                        {elseif $ds['status']==3}{$_L['FAILED']}
                                        {elseif $ds['status']==4}{Lang::T('CANCELED')}
                                        {elseif $ds['status']==5}{Lang::T('UNKNOWN')}
                                        {/if}</td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr><td colspan="9" class="text-center text-muted" style="padding:20px;">{Lang::T('No order records found.')}</td></tr>
                        {/if}
                    </tbody>
                </table>
            </div>

            {elseif $v=='tickets'}
            <div class="table-responsive">
                <table class="table table-bordered table-striped tab-history-table">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Update</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $tickets|@count > 0}
                            {foreach $tickets as $ticket}
                                <tr>
                                    <td><strong>{$ticket.ticket_number}</strong></td>
                                    <td>{$ticket.subject}</td>
                                    <td>{if $ticket.category_name}<span class="badge badge-info">{$ticket.category_name}</span>{else}-{/if}</td>
                                    <td>
                                        {if $ticket.priority == 'Urgent'}<span class="label label-danger">Urgent</span>
                                        {elseif $ticket.priority == 'High'}<span class="label label-warning">High</span>
                                        {elseif $ticket.priority == 'Normal'}<span class="label label-info">Normal</span>
                                        {else}<span class="label label-default">Low</span>{/if}
                                    </td>
                                    <td>
                                        {if $ticket.status == 'Closed'}<span class="label label-success">Closed</span>
                                        {elseif $ticket.status == 'In Progress'}<span class="label label-primary">In Progress</span>
                                        {elseif $ticket.status == 'Pending'}<span class="label label-warning">Pending</span>
                                        {else}<span class="label label-default">Open</span>{/if}
                                    </td>
                                    <td>{date('M d, Y', strtotime($ticket.created_at))}</td>
                                    <td>{date('M d, Y H:i', strtotime($ticket.updated_at))}</td>
                                    <td>
                                        <a href="{$_url}plugin/support_tickets&action=view&id={$ticket.id}" class="btn btn-info btn-xs">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="8" class="text-center" style="padding:20px;">
                                    <p class="text-muted">No support tickets found for this customer.</p>
                                    <a href="{$_url}plugin/support_tickets&action=add" class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus"></i> Create New Ticket
                                    </a>
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>

            {elseif $v=='smslogs'}
            <div class="table-responsive">
                <table class="table table-bordered table-striped tab-history-table">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Phone</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Message ID</th>
                            <th>Error Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $smslogs|@count > 0}
                            {foreach $smslogs as $sms}
                                <tr>
                                    <td>{date('Y-m-d H:i:s', strtotime($sms.created_at))}</td>
                                    <td>{$sms.phone}</td>
                                    <td style="max-width: 300px; word-wrap: break-word; white-space: normal;">{$sms.message}</td>
                                    <td>
                                        {if strtolower($sms.status) == 'sent' || strtolower($sms.status) == 'success'}<span class="label label-success">Sent</span>
                                        {elseif strtolower($sms.status) == 'failed' || strtolower($sms.status) == 'error'}<span class="label label-danger">Failed</span>
                                        {else}<span class="label label-info">{$sms.status}</span>{/if}
                                    </td>
                                    <td><small class="text-muted">{if $sms.message_id}{$sms.message_id}{else}N/A{/if}</small></td>
                                    <td><small class="text-muted" style="color:{if strtolower($sms.status)=='failed'}#c0392b{else}#27ae60{/if};">{if $sms.status_message}{$sms.status_message}{else}-{/if}</small></td>
                                    <td>
                                        <a href="{$_url}customers/resend_sms/{$sms.id}/{$d['id']}&token={$csrf_token}"
                                           class="btn btn-xs btn-default"
                                           onclick="return confirm('Resend this SMS to {$sms.phone}?')">
                                            <i class="fa fa-send"></i> Resend
                                        </a>
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 30px;">
                                    <i class="fa fa-comments-o fa-3x text-muted" style="opacity: 0.3;"></i>
                                    <p class="text-muted" style="margin-top: 10px;">No SMS logs found for this customer.</p>
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>

            {elseif $v=='mklogs'}
            <div class="table-responsive">
                <table class="table table-bordered table-striped tab-history-table">
                    <thead>
                        <tr>
                            <th style="width:15%">Time</th>
                            <th style="width:20%">Topics</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody id="mklogs-tbody">
                        <tr>
                            <td colspan="3" class="text-center" style="padding:24px;">
                                <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted" style="margin-top:8px;">Loading MikroTik logs&hellip;</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            {/if}
            {include file="pagination.tpl"}
            </div>
        </div>
        <div class="row">
            {foreach $packages as $package}
                <div class="col-md-6">
                    <div class="box box-{if $package['status']=='on'}success{else}danger{/if}" data-package-id="{$package['id']}">
                        <div class="box-body box-profile">
                            <h4 class="text-center">{$package['type']} - {$package['namebp']} <span
                                    api-get-text="{$_url}autoload/customer_is_active/{$package['username']}/{$package['plan_id']}"></span>
                                <small class="text-muted" style="font-size:13px;font-weight:normal;"> &nbsp;|&nbsp; {Lang::moneyFormat($package['price'])}</small>
                            </h4>
                            <ul class="list-group list-group-unbordered">
                                <li class="list-group-item">
                                    <b><i class="fa fa-circle sr-ico"></i> {Lang::T('Active')}</b> <span
                                        class="pull-right sr-chip {if $package['status']=='on'}sr-chip-ok{else}sr-chip-bad{/if}">{if $package['status']=='on'}{Lang::T('Yes')}{else}{Lang::T('No')}{/if}</span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fa fa-tag sr-ico"></i> {Lang::T('Type')}</b> <span class="pull-right sr-chip sr-chip-mute">
                                        {if $package['prepaid'] eq yes}{Lang::T('Prepaid')}{else}{Lang::T('Postpaid')}{/if}</span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fa fa-dashboard sr-ico"></i> {Lang::T('Bandwidth')}</b> <span class="pull-right sr-chip sr-chip-mono">
                                        {$package['name_bw']}</span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fa fa-calendar sr-ico"></i> {Lang::T('Created On')}</b> <span
                                        class="pull-right">{Lang::dateAndTimeFormat($package['recharged_on'],$package['recharged_time'])}</span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fa fa-hourglass-end sr-ico"></i> {Lang::T('Expires On')}</b> <span class="pull-right sr-chip sr-chip-money">{Lang::dateAndTimeFormat($package['expiration'],
                        $package['time'])}</span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fa fa-server sr-ico"></i> {$package['routers']}</b> <span class="pull-right sr-chip sr-chip-mono">{$package['method']}</span>
                                </li>
                            </ul>
                            <div class="row" style="margin-bottom: 10px;">
                                {if $_c['extend_expired']}
                                <div class="col-xs-4">
                                    <a href="{$_url}customers/deactivate/{$d['id']}/{$package['plan_id']}&token={$csrf_token}" id="{$d['id']}"
                                        class="btn btn-3d btn-3d-danger btn-sm btn-block"
                                        onclick="return ask(this, 'This will deactivate Customer Plan, and make it expired')">{Lang::T('Deactivate')}</a>
                                </div>
                                <div class="col-xs-4">
                                    <a href="{$_url}plan/edit/{$package['id']}&token={$csrf_token}"
                                        class="btn btn-3d btn-3d-primary btn-sm btn-block">{Lang::T('Edit Plan')}</a>
                                </div>
                                <div class="col-xs-4">
                                    <a href="{$_url}customers/recharge/{$d['id']}/{$package['plan_id']}&token={$csrf_token}"
                                        class="btn btn-3d btn-3d-success btn-sm btn-block">{Lang::T('Recharge')}</a>
                                </div>
                                <div class="col-xs-6" style="margin-top: 5px;">
                                    <button onclick="extendPackage('{$package['id']}')" class="btn btn-3d btn-3d-warning btn-sm btn-block">
                                        <i class="fa fa-clock-o"></i> {Lang::T('Extend')}
                                    </button>
                                </div>
                                <div class="col-xs-6" style="margin-top: 5px;">
                                    <a href="{$_url}customers/delete_package/{$d['id']}/{$package['id']}&token={$csrf_token}" id="{$d['id']}"
                                        class="btn btn-3d btn-3d-danger btn-sm btn-block"
                                        onclick="return ask(this, 'This will permanently delete this package. Are you sure?')"><i class="fa fa-trash"></i> Delete</a>
                                </div>
                                {else}
                                <div class="col-xs-4">
                                    <a href="{$_url}customers/deactivate/{$d['id']}/{$package['plan_id']}&token={$csrf_token}" id="{$d['id']}"
                                        class="btn btn-3d btn-3d-danger btn-sm btn-block"
                                        onclick="return ask(this, 'This will deactivate Customer Plan, and make it expired')">{Lang::T('Deactivate')}</a>
                                </div>
                                <div class="col-xs-4">
                                    <a href="{$_url}plan/edit/{$package['id']}&token={$csrf_token}"
                                        class="btn btn-3d btn-3d-primary btn-sm btn-block">{Lang::T('Edit Plan')}</a>
                                </div>
                                <div class="col-xs-4">
                                    <a href="{$_url}customers/recharge/{$d['id']}/{$package['plan_id']}&token={$csrf_token}"
                                        class="btn btn-3d btn-3d-success btn-sm btn-block">{Lang::T('Recharge')}</a>
                                </div>
                                <div class="col-xs-6" style="margin-top: 5px;">
                                    <button onclick="extendPackage('{$package['id']}')" class="btn btn-3d btn-3d-warning btn-sm btn-block">
                                        <i class="fa fa-clock-o"></i> {Lang::T('Extend')}
                                    </button>
                                </div>
                                <div class="col-xs-6" style="margin-top: 5px;">
                                    <a href="{$_url}customers/delete_package/{$d['id']}/{$package['id']}&token={$csrf_token}" id="{$d['id']}"
                                        class="btn btn-3d btn-3d-danger btn-sm btn-block"
                                        onclick="return ask(this, 'This will permanently delete this package. Are you sure?')"><i class="fa fa-trash"></i> Delete</a>
                                </div>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>

{if isset($devices) && count($devices) > 0}
<div class="row">
    <div class="col-sm-12">
        <div class="box box-primary">
            <div class="box-header with-border" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; padding-bottom:10px;">
                <h3 class="box-title" style="margin:0;"><i class="fa fa-wifi"></i> {Lang::T('Connected Devices')} <span data-toggle="tooltip" title="Total Connected Devices" class="badge bg-blue">{count($devices)}</span></h3>
                <div style="display:flex; flex-wrap:wrap; gap:4px; align-items:center;">
                    {if $customer_enabled === true}
                        <a href="{$_url}customers/disable/{$d['id']}&token={$csrf_token}"
                           onclick="return ask(this, 'This will disable the customer on Mikrotik router and disconnect them. Continue?')"
                           class="btn btn-3d btn-3d-success btn-sm">
                            <i class="fa fa-toggle-on"></i> ON — {Lang::T('Turn Off')}
                        </a>
                    {elseif $customer_enabled === false}
                        <a href="{$_url}customers/enable/{$d['id']}&token={$csrf_token}"
                           onclick="return ask(this, 'This will enable the customer on Mikrotik router. Continue?')"
                           class="btn btn-3d btn-3d-danger btn-sm">
                            <i class="fa fa-toggle-off"></i> OFF — {Lang::T('Turn On')}
                        </a>
                    {else}
                        <span class="label label-default" style="padding:8px 10px;"><i class="fa fa-question-circle"></i> {Lang::T('Status unavailable')}</span>
                    {/if}
                    <a href="{$_url}customers/reconnect/{$d['id']}&token={$csrf_token}" 
                       onclick="return ask(this, 'This will disconnect and reconnect the customer. Continue?')"
                       class="btn btn-3d btn-3d-warning btn-sm">
                        <i class="fa fa-refresh"></i> {Lang::T('Reconnect')}
                    </a>
                </div>
            </div>
            <div class="box-body no-padding">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 10%">{Lang::T('Type')}</th>
                                <th style="width: 15%">{Lang::T('MAC Address')}</th>
                                <th style="width: 12%">{Lang::T('IP Address')}</th>
                                <th style="width: 18%">{Lang::T('Host Name')}</th>
                                <th style="width: 12%">{Lang::T('Download')}</th>
                                <th style="width: 12%">{Lang::T('Upload')}</th>
                                <th style="width: 13%">{Lang::T('Total Usage')}</th>
                                <th style="width: 8%">{Lang::T('Uptime')}</th>
                                <th style="width: 10%">{Lang::T('Status')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $devices as $device}
                                <tr>
                                    <td>
                                        {if $device['type'] eq 'Hotspot'}
                                            <span class="label label-primary" style="font-size: 12px;">
                                                <i class="fa fa-dot-circle-o"></i> {$device['type']}
                                            </span>
                                        {else}
                                            <span class="label label-info" style="font-size: 12px;">
                                                <i class="fa fa-plug"></i> {$device['type']}
                                            </span>
                                        {/if}
                                    </td>
                                    <td>
                                        <code style="background: #f8f9fa; padding: 5px 8px; border-radius: 4px; color: #495057; font-size: 13px;">
                                            <i class="fa fa-microchip"></i> {$device['mac_address']}
                                        </code>
                                    </td>
                                    <td>
                                        <a href="http://{$device['ip_address']}" target="_blank" rel="noopener" title="Open device web interface" style="text-decoration:none;">
                                            <code style="background: #eef4ff; padding: 5px 8px; border-radius: 4px; color: #2563eb; font-size: 13px; cursor: pointer;">
                                                <i class="fa fa-globe"></i> {$device['ip_address']}
                                            </code>
                                        </a>
                                    </td>
                                    <td>
                                        <code style="background: #f8f9fa; padding: 5px 8px; border-radius: 4px; color: #495057; font-size: 13px;">
                                            <i class="fa fa-desktop"></i> {$device['hostname']}
                                        </code>
                                    </td>
                                    {* v2.2.26: mikrotik_device_info.php names PPPoE rx-byte as
                                       bytes_in, and bytes_in is what the ROUTER received from the
                                       customer — that is their UPLOAD. The router's sent counter
                                       (bytes_out) is the customer's DOWNLOAD, so these two cells are
                                       deliberately crossed over. *}
                                    <td>
                                        <span class="label label-success" style="font-size: 11px;">
                                            <i class="fa fa-download"></i> 
                                            <span class="data-usage" data-bytes="{$device['bytes_out']}">Loading...</span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="label label-info" style="font-size: 11px;">
                                            <i class="fa fa-upload"></i> 
                                            <span class="data-usage" data-bytes="{$device['bytes_in']}">Loading...</span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="label label-warning" style="font-size: 11px;">
                                            <i class="fa fa-exchange"></i> 
                                            <span class="total-usage" data-in="{$device['bytes_in']}" data-out="{$device['bytes_out']}">Loading...</span>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fa fa-clock-o"></i> {$device['uptime']}
                                    </td>
                                    <td>
                                        <span class="label label-success">
                                            <i class="fa fa-check-circle"></i> Active
                                        </span>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="box-footer text-center sr-lastupdated">
                <small class="text-muted">
                    <i class="fa fa-info-circle"></i> Last Updated: {$smarty.now|date_format:"%H:%M:%S"}
                </small>
            </div>
        </div>
    </div>
</div>
{else}
<div class="row">
    <div class="col-sm-12">
        <div class="box box-info">
            <div class="box-header with-border" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; padding-bottom:10px;">
                <h3 class="box-title" style="margin:0;"><i class="fa fa-wifi"></i> {Lang::T('Router Control')}</h3>
                <div style="display:flex; flex-wrap:wrap; gap:4px; align-items:center;">
                    {if $customer_enabled === true}
                        <a href="{$_url}customers/disable/{$d['id']}&token={$csrf_token}"
                           onclick="return ask(this, 'This will disable the customer on Mikrotik router and disconnect them. Continue?')"
                           class="btn btn-3d btn-3d-success btn-sm">
                            <i class="fa fa-toggle-on"></i> ON — {Lang::T('Turn Off')}
                        </a>
                    {elseif $customer_enabled === false}
                        <a href="{$_url}customers/enable/{$d['id']}&token={$csrf_token}"
                           onclick="return ask(this, 'This will enable the customer on Mikrotik router. Continue?')"
                           class="btn btn-3d btn-3d-danger btn-sm">
                            <i class="fa fa-toggle-off"></i> OFF — {Lang::T('Turn On')}
                        </a>
                    {else}
                        <span class="label label-default" style="padding:8px 10px;"><i class="fa fa-question-circle"></i> {Lang::T('Status unavailable')}</span>
                    {/if}
                    <a href="{$_url}customers/reconnect/{$d['id']}&token={$csrf_token}" 
                       onclick="return ask(this, 'This will disconnect and reconnect the customer. Continue?')"
                       class="btn btn-3d btn-3d-warning btn-sm">
                        <i class="fa fa-refresh"></i> {Lang::T('Reconnect')}
                    </a>
                </div>
            </div>
            <div class="box-body text-center" style="padding: 40px;">
                <i class="fa fa-wifi" style="font-size: 48px; color: #bbb; margin-bottom: 15px;"></i>
                <h4 style="color: #666;">{Lang::T('No Connected Devices')}</h4>
                <p class="text-muted">{Lang::T('This customer has no active connections at the moment.')}</p>
            </div>
        </div>
    </div>
</div>
{/if}

{* v2.2.25: single action-bar card instead of a bare <hr> + floating row *}
<div class="sr-actions">
    <a href="{$_url}customers/list" class="sr-action sr-action-back">
        <i class="fa fa-arrow-left"></i> {Lang::T('Back')}
    </a>
    <a href="{$_url}customers/sync/{$d['id']}&token={$csrf_token}" onclick="return ask(this, 'This will sync Customer to Mikrotik?')"
        class="sr-action sr-action-sync">
        <i class="fa fa-refresh"></i> {Lang::T('Sync')}
    </a>
    <a href="{$_url}message/send/{$d['id']}&token={$csrf_token}" class="sr-action sr-action-msg">
        <i class="fa fa-envelope"></i> {Lang::T('Send Message')}
    </a>
    <a href="{$_url}customers/login/{$d['id']}&token={$csrf_token}" target="_blank" class="sr-action sr-action-login">
        <i class="fa fa-sign-in"></i> {Lang::T('Login as Customer')}
    </a>
</div>

{if $d['coordinates']}
    {literal}
        <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
        <script>
            function setupMap(lat, lon) {
                var map = L.map('map').setView([lat, lon], 17);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/light_all/{z}/{x}/{y}.png', {
                attribution:
                    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                    subdomains: 'abcd',
                    maxZoom: 20
            }).addTo(map);
            var marker = L.marker([lat, lon]).addTo(map);
            }
            window.onload = function() {
                {/literal}setupMap({$d['coordinates']});{literal}
            }
        </script>
    {/literal}
{/if}
{literal}
<script>
function confirmDelete(url) {
    if (confirm('{/literal}{Lang::T('Delete')}?{literal}')) {
        window.location.href = url;
    }
}

function extendCustomerPlan(customerId) {
    // Get customer's active packages to extend
    var packages = document.querySelectorAll('[data-package-id]');
    if (packages.length === 0) {
        alert('No active packages found for this customer');
        return;
    }
    
    // If only one package, extend it directly
    if (packages.length === 1) {
        var packageId = packages[0].getAttribute('data-package-id');
        extendPackage(packageId);
        return;
    }
    
    // If multiple packages, let user choose or extend the first active one
    var activePackages = [];
    for (var i = 0; i < packages.length; i++) {
        var packageBox = packages[i];
        if (packageBox.classList.contains('box-success')) {
            activePackages.push(packageBox.getAttribute('data-package-id'));
        }
    }
    
    if (activePackages.length > 0) {
        // Extend the first active package
        extendPackage(activePackages[0]);
    } else {
        // No active packages, extend the first one
        extendPackage(packages[0].getAttribute('data-package-id'));
    }
}

function extendPackage(packageId) {
    var days = prompt("Extend for how many days?", "3");
    if (days) {
        if (confirm("Extend for " + days + " days?")) {
            window.location.href = "{/literal}{$_url}plan/extend/{literal}" + packageId + "/" + days + "&stoken={/literal}{App::getToken()}{literal}";
        }
    }
}

// Format bytes into human readable format
function formatBytes(bytes, precision = 2) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    
    for (i = 0; bytes > 1024 && i < units.length - 1; i++) {
        bytes /= 1024;
    }
    
    return bytes.toFixed(precision) + ' ' + units[i];
}

// Update data usage displays
function updateDataUsage() {
    // Update individual data usage
    document.querySelectorAll('.data-usage').forEach(function(element) {
        const bytes = parseInt(element.getAttribute('data-bytes'));
        if (!isNaN(bytes) && bytes > 0) {
            element.textContent = formatBytes(bytes);
        } else {
            element.textContent = '0 B';
        }
    });
    
    // Update total usage
    document.querySelectorAll('.total-usage').forEach(function(element) {
        const bytesIn = parseInt(element.getAttribute('data-in'));
        const bytesOut = parseInt(element.getAttribute('data-out'));
        if (!isNaN(bytesIn) && !isNaN(bytesOut)) {
            const total = bytesIn + bytesOut;
            element.textContent = formatBytes(total);
        } else {
            element.textContent = '0 B';
        }
    });
}

// Initialize data usage when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateDataUsage();
});

// Auto-refresh data usage every 30 seconds
setInterval(function() {
    // You could add AJAX call here to refresh device data
    // For now, just update the formatting
    updateDataUsage();
}, 30000);
</script>
{/literal}

{* ── Live Bandwidth Graph ──────────────────────────────────────────────── *}
<div class="row" style="margin-top:8px;">
    <div class="col-sm-12">
        <div class="box box-primary" id="live-graph-box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-line-chart"></i> Live Bandwidth</h3>
                <div class="box-tools pull-right" style="display:flex;align-items:center;gap:8px;">
                    <span id="live-session-type" class="label label-default sr-live-badge">Detecting&hellip;</span>
                    <span id="live-ip" class="text-muted" style="font-size:12px;"></span>
                    <span id="live-uptime" class="text-muted" style="font-size:12px;"></span>
                    <button id="live-toggle" class="sr-live-toggle" title="Pause/Resume">
                        <i class="fa fa-pause" id="live-toggle-icon"></i>
                    </button>
                </div>
            </div>
            <div class="box-body" style="padding-bottom:6px;">
                <div class="row" style="margin-bottom:10px;">
                    <div class="col-xs-4">
                        <div class="sr-metric sr-metric-dl">
                            <span class="sr-metric-label">Download</span>
                            <span id="live-dl-speed" class="sr-metric-value">0 bps</span>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="sr-metric sr-metric-ul">
                            <span class="sr-metric-label">Upload</span>
                            <span id="live-ul-speed" class="sr-metric-value">0 bps</span>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="sr-metric sr-metric-tot">
                            <span class="sr-metric-label">Session Total DL</span>
                            <span id="live-total-dl" class="sr-metric-value">0 B</span>
                        </div>
                    </div>
                </div>
                <div style="position:relative;height:180px;">
                    <canvas id="liveChart"></canvas>
                </div>
            </div>
            <div class="box-footer sr-bandwidth-footer">
                <small class="text-muted"><i class="fa fa-refresh"></i> Updates every 3 seconds &nbsp;|&nbsp; <span id="live-status-text">Starting&hellip;</span></small>
            </div>
        </div>
    </div>
</div>
{* ── End Live Bandwidth Graph ────────────────────────────────────────────── *}

{* ── Monthly Data Usage ─────────────────────────────────────────────────── *}
<style>
.usage-card {
    border-radius: 8px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.usage-card .usage-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.usage-card .usage-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.usage-card .usage-value {
    font-size: 18px;
    font-weight: 700;
    line-height: 1;
}
.usage-card-dl  { background:#f0faf2; border-left: 3px solid #27ae60; }
.usage-card-ul  { background:#f0f5ff; border-left: 3px solid #2980b9; }
.usage-card-tot { background:#fff8f0; border-left: 3px solid #e67e22; }
.usage-card-dl  .usage-icon { background:#27ae60; color:#fff; }
.usage-card-ul  .usage-icon { background:#2980b9; color:#fff; }
.usage-card-tot .usage-icon { background:#e67e22; color:#fff; }
.usage-card-dl  .usage-label { color:#27ae60; }
.usage-card-ul  .usage-label { color:#2980b9; }
.usage-card-tot .usage-label { color:#e67e22; }
.usage-card-dl  .usage-value { color:#1e8449; }
.usage-card-ul  .usage-value { color:#1f618d; }
.usage-card-tot .usage-value { color:#ca6f1e; }
.usage-history-table thead th {
    background: #f8f9fa;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #555;
    font-weight: 600;
    border-bottom: 2px solid #e9ecef;
    padding: 8px 12px;
    white-space: nowrap;
}
.usage-history-table tbody td {
    font-size: 13px;
    padding: 8px 12px;
    vertical-align: middle;
}
.usage-history-table tbody tr.current-month {
    background: #fffbea !important;
}
.usage-badge-current {
    display: inline-block;
    background: #f39c12;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
    letter-spacing: 0.3px;
    vertical-align: middle;
    margin-right: 4px;
}
.usage-section-header {
    background: #2c3e50;
    border-radius: 8px 8px 0 0;
    padding: 11px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.usage-section-header .title {
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    margin: 0;
}
.usage-section-header .subtitle {
    color: #aab7c4;
    font-size: 11px;
}
</style>

<div class="row" style="margin-top:8px;">
    <div class="col-sm-12">
        <div class="box box-default" style="border-radius:8px; overflow:hidden; border:none; box-shadow:0 1px 4px rgba(0,0,0,0.1);">
            <div class="usage-section-header">
                <span class="title"><i class="fa fa-bar-chart" style="margin-right:6px;"></i>Monthly Data Usage</span>
                <span class="subtitle"><i class="fa fa-refresh" style="margin-right:4px;"></i>Resets on the 1st of every month</span>
            </div>
            <div class="box-body" style="padding:14px 14px 6px;">

                <div class="row">
                    <div class="col-xs-12 col-sm-4">
                        <div class="usage-card usage-card-dl">
                            <div class="usage-icon"><i class="fa fa-download"></i></div>
                            <div>
                                <div class="usage-label">Downloaded</div>
                                <div class="usage-value">
                                    {if $monthly_usage_current}{$monthly_usage_current['download_fmt']}{else}0 B{/if}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-4">
                        <div class="usage-card usage-card-ul">
                            <div class="usage-icon"><i class="fa fa-upload"></i></div>
                            <div>
                                <div class="usage-label">Uploaded</div>
                                <div class="usage-value">
                                    {if $monthly_usage_current}{$monthly_usage_current['upload_fmt']}{else}0 B{/if}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-4">
                        <div class="usage-card usage-card-tot">
                            <div class="usage-icon"><i class="fa fa-exchange"></i></div>
                            <div>
                                <div class="usage-label">Total This Month</div>
                                <div class="usage-value">
                                    {if $monthly_usage_current}{$monthly_usage_current['total_fmt']}{else}0 B{/if}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {if $monthly_usage_history|@count > 0}
                    <div class="table-responsive" style="margin-top:6px;">
                        <table class="table table-hover usage-history-table" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th><i class="fa fa-download" style="color:#27ae60;"></i> Downloaded</th>
                                    <th><i class="fa fa-upload" style="color:#2980b9;"></i> Uploaded</th>
                                    <th><i class="fa fa-exchange" style="color:#e67e22;"></i> Total</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $monthly_usage_history as $mu}
                                    <tr {if $mu['is_current']}class="current-month"{/if}>
                                        <td>
                                            {if $mu['is_current']}<span class="usage-badge-current">NOW</span>{/if}
                                            {$mu['month_label']}
                                        </td>
                                        <td style="color:#1e8449; font-weight:600;">{$mu['download_fmt']}</td>
                                        <td style="color:#1f618d; font-weight:600;">{$mu['upload_fmt']}</td>
                                        <td style="color:#ca6f1e; font-weight:600;">{$mu['total_fmt']}</td>
                                        <td><small class="text-muted">{$mu['last_updated']}</small></td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {else}
                    <div class="text-center" style="padding:24px 0 16px; color:#bbb;">
                        <i class="fa fa-bar-chart" style="font-size:28px; opacity:0.35;"></i>
                        <p style="margin:8px 0 0; font-size:13px; color:#999;">No data usage recorded yet.
                            <br><small>Updated automatically on each cron run.</small>
                        </p>
                    </div>
                {/if}

            </div>
        </div>
    </div>
</div>
{* ── End Monthly Data Usage ──────────────────────────────────────────────── *}

</div>
{* ── end .sr-cust ────────────────────────────────────────────────────────────── *}

{* ── Live Graph + MT Logs JS ─────────────────────────────────────────────── *}
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
{literal}
<script>
(function () {
    'use strict';

    // ── helpers ──────────────────────────────────────────────────────────────
    function fmtSpeed(bytesPerSec) {
        var b = bytesPerSec * 8; // convert to bits/s
        if (b >= 1e9)  return (b / 1e9).toFixed(2) + ' Gbps';
        if (b >= 1e6)  return (b / 1e6).toFixed(2) + ' Mbps';
        if (b >= 1e3)  return (b / 1e3).toFixed(2) + ' Kbps';
        return b.toFixed(0) + ' bps';
    }
    function fmtBytes(bytes) {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576)    return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024)       return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    }
    function nowLabel() {
        var d = new Date();
        return d.getHours().toString().padStart(2,'0') + ':' +
               d.getMinutes().toString().padStart(2,'0') + ':' +
               d.getSeconds().toString().padStart(2,'0');
    }

    // ── Chart setup ──────────────────────────────────────────────────────────
    var MAX_PTS = 60;
    var labels  = Array(MAX_PTS).fill('');
    var dlData  = Array(MAX_PTS).fill(0);
    var ulData  = Array(MAX_PTS).fill(0);

    var ctx = document.getElementById('liveChart');
    if (!ctx) return;

    var liveChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Download',
                    data: dlData,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39,174,96,0.08)',
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Upload',
                    data: ulData,
                    borderColor: '#2980b9',
                    backgroundColor: 'rgba(41,128,185,0.08)',
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 0 },
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: {
                x: { ticks: { font: { size: 10 }, maxTicksLimit: 10 }, grid: { display: false } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 10 },
                        callback: function(v) {
                            if (v === 0) return '0';
                            return fmtSpeed(v);
                        }
                    },
                    title: { display: true, text: 'Speed', font: { size: 10 } }
                }
            }
        }
    });

    // ── Live polling ─────────────────────────────────────────────────────────
    var prevBytes    = null;
    var paused       = false;
    var pollInterval = null;
{/literal}
    var customerId   = '{$d['id']}';
    var apiBase      = '{$_url}customers/live_stats/';
{literal}

    document.getElementById('live-toggle').addEventListener('click', function () {
        paused = !paused;
        var icon = document.getElementById('live-toggle-icon');
        icon.className = paused ? 'fa fa-play' : 'fa fa-pause';
        document.getElementById('live-status-text').textContent = paused ? 'Paused' : 'Running…';
    });

    function pollLiveStats() {
        if (paused) return;
        fetch(apiBase + customerId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var statusEl = document.getElementById('live-status-text');
                if (!data.success) {
                    statusEl.textContent = data.error || 'Error';
                    return;
                }

                // Session type badge
                var badge = document.getElementById('live-session-type');
                if (data.type === 'offline') {
                    badge.className = 'label label-danger';
                    badge.textContent = 'Offline';
                    statusEl.textContent = 'No active session';
                } else {
                    badge.className = 'label label-success';
                    badge.textContent = data.type;
                    statusEl.textContent = 'Live';
                }

                // IP + uptime
                document.getElementById('live-ip').textContent     = data.ip     ? ('IP: ' + data.ip)     : '';
                document.getElementById('live-uptime').textContent = data.uptime ? ('Up: ' + data.uptime) : '';

                // Session total download — the endpoint emits explicitly named
                // download/upload keys (v2.2.26), so the two can no longer be crossed.
                document.getElementById('live-total-dl').textContent = fmtBytes(data.download);

                // Compute speed from delta
                var dlSpeed = 0, ulSpeed = 0;
                if (prevBytes && data.type !== 'offline') {
                    var dt = data.timestamp - prevBytes.ts;
                    if (dt > 0) {
                        dlSpeed = Math.max(0, (data.download - prevBytes.dl) / dt);
                        ulSpeed = Math.max(0, (data.upload   - prevBytes.ul) / dt);
                    }
                }
                prevBytes = { dl: data.download, ul: data.upload, ts: data.timestamp };

                // Update speed cards
                document.getElementById('live-dl-speed').textContent = fmtSpeed(dlSpeed);
                document.getElementById('live-ul-speed').textContent = fmtSpeed(ulSpeed);

                // Push to chart
                labels.push(nowLabel());   labels.shift();
                dlData.push(dlSpeed);      dlData.shift();
                ulData.push(ulSpeed);      ulData.shift();
                liveChart.update('none');
            })
            .catch(function() {
                document.getElementById('live-status-text').textContent = 'Connection error';
            });
    }

    // Start polling immediately then every 3 s
    pollLiveStats();
    pollInterval = setInterval(pollLiveStats, 3000);

    // ── MikroTik Logs (mklogs tab) ───────────────────────────────────────────
    var mklogsTbody = document.getElementById('mklogs-tbody');
    if (mklogsTbody) {
{/literal}
        var logsApiUrl = '{$_url}customers/mikrotik_logs/{$d['id']}';
{literal}
        // topic → { bg, border, icon, text }
        var TOPIC_STYLE = {
            'info':     { bg:'#e8f4fd', border:'#3498db', icon:'fa-info-circle',   text:'#1a6a9a' },
            'warning':  { bg:'#fff8e1', border:'#f39c12', icon:'fa-exclamation-triangle', text:'#9a6800' },
            'error':    { bg:'#fdecea', border:'#e74c3c', icon:'fa-times-circle',   text:'#a93226' },
            'critical': { bg:'#fdecea', border:'#c0392b', icon:'fa-bomb',           text:'#7b241c' },
            'debug':    { bg:'#f0f0f0', border:'#95a5a6', icon:'fa-bug',            text:'#555' },
            'firewall': { bg:'#fef9e7', border:'#e67e22', icon:'fa-shield',         text:'#9a4f00' },
            'ppp':      { bg:'#eaf7fb', border:'#16a085', icon:'fa-plug',           text:'#0e6655' },
            'hotspot':  { bg:'#eaf5ea', border:'#27ae60', icon:'fa-wifi',           text:'#1a7a40' },
            'dhcp':     { bg:'#f4ecf7', border:'#8e44ad', icon:'fa-sitemap',        text:'#6c3483' },
            'system':   { bg:'#eaf0fb', border:'#2980b9', icon:'fa-cogs',           text:'#1a5276' }
        };

        function getTopicStyle(topics) {
            var t = (topics || '').toLowerCase();
            for (var key in TOPIC_STYLE) {
                if (t.indexOf(key) !== -1) return TOPIC_STYLE[key];
            }
            return { bg:'#f8f9fa', border:'#bdc3c7', icon:'fa-list', text:'#555' };
        }

        fetch(logsApiUrl)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.logs || data.logs.length === 0) {
                    mklogsTbody.innerHTML =
                        '<tr><td colspan="3" class="text-center" style="padding:40px 20px;">' +
                        '<i class="fa fa-list-alt" style="font-size:36px;color:#ccc;display:block;margin-bottom:10px;"></i>' +
                        '<span style="color:#999;font-size:13px;">' + escHtml(data.error || 'No log entries found for this user.') + '</span>' +
                        '</td></tr>';
                    return;
                }
                var rows = '';
                data.logs.forEach(function(log, idx) {
                    var s = getTopicStyle(log.topics);
                    rows +=
                        '<tr style="background:' + s.bg + ';border-left:4px solid ' + s.border + ';">' +
                        '<td style="white-space:nowrap;font-size:11px;color:#666;vertical-align:middle;padding:8px 10px;">' +
                            '<i class="fa fa-clock-o" style="margin-right:3px;"></i>' + escHtml(log.time) +
                        '</td>' +
                        '<td style="vertical-align:middle;padding:8px 10px;">' +
                            '<span style="display:inline-flex;align-items:center;gap:4px;background:' + s.border + ';' +
                            'color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;letter-spacing:.3px;">' +
                            '<i class="fa ' + s.icon + '"></i>' +
                            escHtml(log.topics) +
                            '</span>' +
                        '</td>' +
                        '<td style="font-size:12px;color:' + s.text + ';word-break:break-all;vertical-align:middle;padding:8px 10px;font-weight:500;">' +
                            escHtml(log.message) +
                        '</td>' +
                        '</tr>';
                });
                mklogsTbody.innerHTML = rows;
            })
            .catch(function() {
                mklogsTbody.innerHTML =
                    '<tr><td colspan="3" class="text-center" style="padding:20px;color:#e74c3c;">' +
                    '<i class="fa fa-exclamation-circle"></i> Failed to load MikroTik logs.</td></tr>';
            });
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})();
</script>
{/literal}
{* ── End Live Graph + MT Logs JS ─────────────────────────────────────────── *}

{include file="sections/footer.tpl"}