@once
<style>
    /* Messaging: ResiSquare brand (navy + orange), shared by the workspace, tenant portal and contractor layouts. */
    .msg-shell {
        --msg-ink: #0b1220; --msg-muted: #5b6573; --msg-faint: #8a93a3; --msg-line: #e6e9ef; --msg-wash: #f4f6f8;
        --msg-card: #fff; --msg-navy: #0f173a; --msg-navy-2: #1a2454; --msg-orange: #ff5c1d; --msg-orange-soft: #ffefe8;
        --msg-dock: 0px;
        max-width: 1280px; margin: 0 auto; padding: 4px 16px 24px; box-sizing: border-box; color: var(--msg-ink);
        container-type: inline-size;
    }
    .msg-shell *, .msg-shell *::before, .msg-shell *::after { box-sizing: border-box; }

    /* Page header */
    .msg-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
    .msg-head h1 { font-size: 1.5rem; font-weight: 700; margin: 0; letter-spacing: -.01em; }
    .msg-head-sub { color: var(--msg-muted); font-size: .875rem; margin-top: 2px; }
    .msg-tabs { display: inline-flex; gap: 2px; padding: 3px; background: #eceff3; border-radius: 12px; }
    .msg-tabs a { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 9px; color: var(--msg-muted); text-decoration: none; font-size: .875rem; font-weight: 600; transition: background .15s, color .15s; }
    .msg-tabs a:hover { color: var(--msg-ink); }
    .msg-tabs a.is-active { background: var(--msg-card); color: var(--msg-navy); box-shadow: 0 1px 2px rgba(15, 23, 58, .08), 0 1px 1px rgba(15, 23, 58, .04); }
    .msg-alert { display: flex; gap: 8px; align-items: flex-start; padding: 10px 14px; border-radius: 12px; font-size: .875rem; margin-bottom: 12px; }
    .msg-alert.is-ok { background: #dcfce7; color: #166534; }
    .msg-alert.is-warn { background: #fef3c7; color: #92400e; }

    /* Two-pane layout */
    .msg-layout { display: grid; grid-template-columns: minmax(260px, 340px) minmax(0, 1fr); background: var(--msg-card); border: 1px solid var(--msg-line); border-radius: 16px; overflow: hidden; height: clamp(520px, calc(100vh - 190px), 900px); box-shadow: 0 1px 3px rgba(15, 23, 58, .04); }

    .msg-layout.is-empty { grid-template-columns: minmax(0, 1fr); height: auto; min-height: 360px; }
    .msg-layout.is-empty .msg-pane { display: none; }
    .msg-layout.is-empty .msg-list { border-right: 0; background: var(--msg-card); justify-content: center; }

    /* Chat list */
    .msg-list { display: flex; flex-direction: column; min-height: 0; border-right: 1px solid var(--msg-line); background: #fbfcfd; }
    .msg-list-search { padding: 12px; border-bottom: 1px solid var(--msg-line); }
    .msg-search { position: relative; }
    .msg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--msg-faint); font-size: .9rem; pointer-events: none; }
    .msg-search input { width: 100%; height: 38px; border: 1px solid var(--msg-line); border-radius: 10px; padding: 0 12px 0 34px; font-size: .875rem; background: var(--msg-card); color: var(--msg-ink); }
    .msg-search input:focus { outline: none; border-color: var(--msg-navy); box-shadow: 0 0 0 3px rgba(15, 23, 58, .08); }
    .msg-list-items { flex: 1; overflow-y: auto; padding: 6px; }
    .msg-item { position: relative; display: grid; grid-template-columns: 44px minmax(0, 1fr); gap: 12px; align-items: center; padding: 10px; border-radius: 12px; color: inherit; text-decoration: none; transition: background .12s; }
    .msg-item + .msg-item { margin-top: 2px; }
    .msg-item:hover { background: #f1f3f6; color: inherit; }
    .msg-item.is-active { background: var(--msg-card); box-shadow: inset 0 0 0 1px var(--msg-line), 0 1px 2px rgba(15, 23, 58, .05); }
    .msg-item.is-active::before { content: ''; position: absolute; left: 0; top: 12px; bottom: 12px; width: 3px; border-radius: 3px; background: var(--msg-orange); }
    .msg-item-top { display: flex; justify-content: space-between; gap: 8px; align-items: baseline; }
    .msg-item-title { font-weight: 600; font-size: .925rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .msg-item-time { color: var(--msg-faint); font-size: .75rem; white-space: nowrap; }
    .msg-item-label { font-size: .75rem; color: var(--msg-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 1px; }
    .msg-item-bottom { display: flex; align-items: center; gap: 8px; margin-top: 3px; }
    .msg-item-preview { flex: 1; min-width: 0; font-size: .825rem; color: var(--msg-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .msg-item.is-unread .msg-item-title { color: var(--msg-ink); font-weight: 700; }
    .msg-item.is-unread .msg-item-preview { color: var(--msg-ink); font-weight: 500; }
    .msg-item.is-unread .msg-item-time { color: var(--msg-orange); font-weight: 600; }
    .msg-pill { flex: none; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px; background: var(--msg-orange); color: #fff; font-size: .7rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
    .msg-list-none { display: none; padding: 24px 12px; text-align: center; color: var(--msg-muted); font-size: .85rem; }

    /* Avatars */
    .msg-avatar { flex: none; width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; letter-spacing: .02em; background: #e8ecf8; color: var(--msg-navy); }
    .msg-avatar.is-lg { width: 44px; height: 44px; font-size: 1.05rem; }
    .msg-avatar.is-sm { width: 28px; height: 28px; font-size: .65rem; }
    .msg-avatar.is-kind { border-radius: 12px; }
    .msg-avatar.is-kind.is-home { background: #e8ecf8; color: var(--msg-navy); }
    .msg-avatar.is-kind.is-job { background: var(--msg-orange-soft); color: #c2410c; }
    .msg-avatar.is-ring { box-shadow: 0 0 0 2px var(--msg-card); }

    /* Conversation pane */
    .msg-pane { display: flex; flex-direction: column; min-width: 0; min-height: 0; background: var(--msg-card); }
    .msg-thread-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--msg-line); background: var(--msg-card); }
    .msg-thread-id { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .msg-thread-id > div { min-width: 0; }
    .msg-thread-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .msg-thread-sub { color: var(--msg-muted); font-size: .8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .msg-people { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
    .msg-person { display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px 2px 2px; border-radius: 999px; background: var(--msg-wash); font-size: .75rem; color: var(--msg-muted); }
    .msg-person strong { color: var(--msg-ink); font-weight: 600; }
    .msg-person .msg-avatar { width: 22px; height: 22px; font-size: .58rem; }
    .msg-back { display: none; align-items: center; gap: 4px; margin: -4px 0 6px -2px; padding: 4px 6px; border-radius: 8px; color: var(--msg-navy); font-size: .85rem; font-weight: 600; text-decoration: none; }
    .msg-back:hover { background: var(--msg-wash); color: var(--msg-navy); }

    .msg-icon-btn { width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--msg-line); background: var(--msg-card); color: var(--msg-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background .12s, color .12s; }
    .msg-icon-btn:hover { background: var(--msg-wash); color: var(--msg-ink); }
    details.msg-menu { position: relative; flex: none; }
    details.msg-menu summary { list-style: none; }
    details.msg-menu summary::-webkit-details-marker { display: none; }
    details.msg-menu[open] summary { background: var(--msg-wash); color: var(--msg-ink); }
    .msg-menu-body { position: absolute; right: 0; top: calc(100% + 6px); z-index: 30; width: min(300px, calc(100vw - 32px)); background: var(--msg-card); border: 1px solid var(--msg-line); border-radius: 14px; padding: 14px; box-shadow: 0 12px 32px rgba(15, 23, 58, .14); }
    .msg-menu-body h3 { font-size: .9rem; font-weight: 700; margin: 0 0 4px; }
    .msg-menu-body p { font-size: .8rem; color: var(--msg-muted); margin: 0 0 10px; }
    .msg-menu-body textarea { width: 100%; border: 1px solid var(--msg-line); border-radius: 10px; padding: 8px 10px; font-size: .85rem; resize: vertical; margin-bottom: 10px; }
    .msg-btn-danger { border: 1px solid #fecaca; background: #fff5f5; color: #b91c1c; border-radius: 10px; padding: 6px 12px; font-size: .825rem; font-weight: 600; }
    .msg-btn-danger:hover { background: #fee2e2; }

    .msg-note { display: flex; gap: 10px; align-items: center; padding: 10px 20px; background: #fffbeb; border-bottom: 1px solid #fde68a; color: #92400e; font-size: .85rem; }

    /* Thread */
    .msg-thread { flex: 1; min-height: 0; overflow-y: auto; padding: 20px 24px 12px; display: flex; flex-direction: column; background: var(--msg-wash); background-image: radial-gradient(rgba(15, 23, 58, .035) 1px, transparent 1px); background-size: 18px 18px; }
    .msg-day { align-self: center; margin: 14px 0 10px; padding: 4px 12px; border-radius: 999px; background: var(--msg-card); border: 1px solid var(--msg-line); color: var(--msg-muted); font-size: .72rem; font-weight: 600; letter-spacing: .02em; }
    .msg-thread > .msg-day:first-child { margin-top: 0; }
    .msg-row { display: flex; align-items: flex-end; gap: 8px; margin-top: 10px; max-width: 100%; }
    .msg-row.is-grouped { margin-top: 3px; }
    .msg-row.is-mine { justify-content: flex-end; }
    .msg-row .msg-avatar-slot { flex: none; width: 30px; }
    .msg-row.is-grouped .msg-avatar-slot .msg-avatar { visibility: hidden; }
    .msg-bubble { max-width: min(600px, 78%); padding: 9px 13px 7px; border-radius: 16px; background: var(--msg-card); color: var(--msg-ink); box-shadow: 0 1px 1px rgba(15, 23, 58, .06); border: 1px solid #eceef2; word-wrap: break-word; overflow-wrap: anywhere; font-size: .925rem; line-height: 1.45; }
    .msg-row:not(.is-mine) .msg-bubble { border-bottom-left-radius: 6px; }
    .msg-row.is-mine .msg-bubble { background: var(--msg-navy); border-color: var(--msg-navy); color: #fff; border-bottom-right-radius: 6px; }
    .msg-row.is-grouped:not(.is-mine) .msg-bubble { border-top-left-radius: 6px; }
    .msg-row.is-grouped.is-mine .msg-bubble { border-top-right-radius: 6px; }
    .msg-author { font-size: .75rem; font-weight: 700; color: #c2410c; margin-bottom: 2px; }
    .msg-row.is-mine .msg-author { color: rgba(255, 255, 255, .75); }
    .msg-meta { display: flex; justify-content: flex-end; gap: 6px; margin-top: 3px; font-size: .68rem; color: var(--msg-faint); }
    .msg-row.is-mine .msg-meta { color: rgba(255, 255, 255, .65); }
    .msg-row.is-mine a { color: #fff; }
    .msg-files { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
    .msg-files img { max-width: 220px; max-height: 160px; border-radius: 10px; display: block; object-fit: cover; }
    .msg-file { display: inline-flex; gap: 6px; align-items: center; padding: 6px 10px; border-radius: 10px; background: var(--msg-wash); color: var(--msg-ink); font-size: .8rem; font-weight: 500; text-decoration: none; }
    .msg-row.is-mine .msg-file { background: rgba(255, 255, 255, .12); color: #fff; }
    .msg-system { align-self: center; display: flex; gap: 8px; align-items: flex-start; max-width: min(560px, 92%); margin: 12px 0 4px; padding: 8px 14px; border-radius: 12px; background: #eef1f6; color: var(--msg-muted); font-size: .8rem; line-height: 1.45; }
    .msg-system i { color: var(--msg-navy); margin-top: 1px; }
    .msg-system time { display: block; color: var(--msg-faint); font-size: .7rem; margin-top: 2px; }
    .msg-divider { display: flex; align-items: center; gap: 10px; margin: 14px 0 4px; color: var(--msg-orange); font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .msg-divider::before, .msg-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255, 92, 29, .35); }

    /* Empty states */
    .msg-empty { margin: auto; text-align: center; color: var(--msg-muted); padding: 40px 24px; max-width: 360px; font-size: .875rem; }
    .msg-empty-icon { width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 20px; background: var(--msg-orange-soft); color: var(--msg-orange); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; }
    .msg-empty h3 { font-size: 1rem; font-weight: 700; color: var(--msg-ink); margin: 0 0 6px; }
    .msg-empty p { margin: 0; line-height: 1.5; }

    /* Composer */
    .msg-composer { padding: 12px 16px 14px; border-top: 1px solid var(--msg-line); background: var(--msg-card); }
    .msg-compose-box { display: flex; align-items: flex-end; gap: 8px; padding: 6px 6px 6px 8px; border: 1px solid var(--msg-line); border-radius: 16px; background: var(--msg-card); transition: border-color .15s, box-shadow .15s; }
    .msg-compose-box:focus-within { border-color: var(--msg-navy); box-shadow: 0 0 0 3px rgba(15, 23, 58, .08); }
    .msg-compose-box textarea { flex: 1; min-width: 0; border: 0; outline: none; resize: none; padding: 8px 4px; min-height: 40px; max-height: 160px; font-size: .925rem; line-height: 1.45; background: transparent; color: var(--msg-ink); }
    .msg-attach { flex: none; width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: var(--msg-muted); cursor: pointer; margin: 0; font-size: 1.1rem; transition: background .12s, color .12s; }
    .msg-attach:hover, .msg-attach:focus-within { background: var(--msg-wash); color: var(--msg-ink); }
    .msg-attach input { position: absolute; width: 1px; height: 1px; opacity: 0; overflow: hidden; }
    .msg-send { flex: none; height: 40px; padding: 0 16px; border: 0; border-radius: 12px; background: var(--msg-navy); color: #fff; font-weight: 600; font-size: .875rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: background .12s, opacity .12s; }
    .msg-send:hover { background: var(--msg-navy-2); }
    .msg-send:disabled { opacity: .45; cursor: default; }
    .msg-compose-files { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
    .msg-compose-files:empty { display: none; }
    .msg-chip { display: inline-flex; align-items: center; gap: 6px; max-width: 240px; padding: 4px 10px; border-radius: 999px; background: var(--msg-wash); border: 1px solid var(--msg-line); font-size: .75rem; color: var(--msg-ink); }
    .msg-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .msg-compose-hint { display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; margin-top: 6px; padding: 0 4px; color: var(--msg-faint); font-size: .72rem; }
    .msg-readonly { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px; border-top: 1px solid var(--msg-line); color: var(--msg-muted); font-size: .85rem; background: var(--msg-card); }

    /* The contractor layout does not load landlord-workspace.css. */
    .msg-shell .btn.lw-btn-primary { background: var(--msg-navy); border-color: var(--msg-navy); color: #fff; font-weight: 600; border-radius: 10px; }

    /* One pane at a time when the content area is narrow (phone, or a cramped desktop). */
    @container (max-width: 720px) {
        .msg-layout { display: block; height: auto; border-radius: 16px; overflow: visible; }
        .msg-list { border-right: 0; background: var(--msg-card); border-radius: 16px; }
        .msg-list-items { overflow: visible; }
        .msg-layout.has-thread .msg-list { display: none; }
        .msg-layout:not(.has-thread) .msg-pane { display: none; }
        .msg-pane { border-radius: 16px; }
        .msg-back { display: inline-flex; }
        .msg-thread-head { align-items: flex-start; padding: 12px 14px; border-radius: 16px 16px 0 0; }
        .msg-thread-head h2 { white-space: normal; }
        .msg-thread { overflow: visible; padding: 16px 12px 8px; min-height: 40vh; }
        .msg-bubble { max-width: 84%; }
        .msg-row .msg-avatar-slot { width: 26px; }
        .msg-row .msg-avatar-slot .msg-avatar { width: 26px; height: 26px; font-size: .6rem; }
        /* Keep the composer on screen while reading, above any phone tab bar. */
        .msg-composer, .msg-readonly { position: sticky; bottom: var(--msg-dock); z-index: 5; border-radius: 0 0 16px 16px; box-shadow: 0 -6px 16px rgba(15, 23, 58, .06); }
        .msg-send-label { display: none; }
        .msg-send { width: 40px; padding: 0; justify-content: center; }
        .msg-compose-hint .msg-hint-keys, .msg-compose-hint .msg-hint-who { display: none; }
    }
    @media (max-width: 767.98px) {
        .msg-shell { padding: 0 10px 16px; }
        .msg-head { align-items: center; margin-bottom: 12px; }
        .msg-head h1 { font-size: 1.3rem; }
        .msg-head-sub { display: none; }
    }
</style>
@endonce
