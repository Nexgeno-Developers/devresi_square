@once
<style>
    .msg-shell { --msg-border: #e3e6ea; --msg-muted: #6b7280; --msg-bg: #fff; --msg-soft: #f5f7fa; --msg-mine: #1f6feb; --msg-mine-text: #fff; --msg-theirs: #f1f3f5; --msg-accent: #1f6feb; max-width: 1200px; margin: 0 auto; padding: 0 16px 24px; box-sizing: border-box; }
    .msg-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
    .msg-head h1 { font-size: 1.4rem; margin: 0; }
    .msg-tabs { display: flex; gap: 4px; flex-wrap: wrap; }
    .msg-tabs a { padding: 6px 12px; border-radius: 999px; color: #374151; text-decoration: none; font-size: .9rem; border: 1px solid transparent; }
    .msg-tabs a.is-active { background: var(--msg-soft); border-color: var(--msg-border); font-weight: 600; }
    .msg-pill { display: inline-block; min-width: 20px; padding: 0 6px; border-radius: 999px; background: #dc3545; color: #fff; font-size: .75rem; text-align: center; line-height: 20px; margin-left: 4px; }
    .msg-shell { container-type: inline-size; }
    .msg-layout { display: grid; grid-template-columns: minmax(220px, 320px) minmax(0, 1fr); border: 1px solid var(--msg-border); border-radius: 12px; background: var(--msg-bg); min-height: 60vh; overflow: hidden; }
    .msg-list { border-right: 1px solid var(--msg-border); overflow-y: auto; max-height: 75vh; }
    .msg-list a.msg-item { display: block; padding: 12px 14px; border-bottom: 1px solid var(--msg-border); color: inherit; text-decoration: none; }
    .msg-list a.msg-item:hover, .msg-list a.msg-item.is-active { background: var(--msg-soft); }
    .msg-item-top { display: flex; justify-content: space-between; gap: 8px; align-items: baseline; }
    .msg-item-title { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .msg-item.is-unread .msg-item-title::before { content: ''; display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--msg-accent); margin-right: 6px; vertical-align: middle; }
    .msg-item-time, .msg-muted { color: var(--msg-muted); font-size: .8rem; }
    .msg-item-label { font-size: .75rem; color: var(--msg-muted); }
    .msg-item-preview { font-size: .85rem; color: #4b5563; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .msg-pane { display: flex; flex-direction: column; min-width: 0; }
    .msg-empty { margin: auto; text-align: center; color: var(--msg-muted); padding: 40px 16px; }
    .msg-thread-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 12px 16px; border-bottom: 1px solid var(--msg-border); flex-wrap: wrap; }
    .msg-thread-head h2 { font-size: 1.05rem; margin: 0; }
    .msg-thread { flex: 1; overflow-y: auto; padding: 16px; max-height: 58vh; display: flex; flex-direction: column; gap: 10px; }
    .msg-bubble { max-width: min(560px, 85%); padding: 8px 12px; border-radius: 14px; background: var(--msg-theirs); align-self: flex-start; word-wrap: break-word; overflow-wrap: anywhere; }
    .msg-bubble.is-mine { align-self: flex-end; background: var(--msg-mine); color: var(--msg-mine-text); }
    .msg-bubble.is-mine .msg-muted, .msg-bubble.is-mine a { color: rgba(255,255,255,.85); }
    .msg-bubble.is-gone { background: transparent; border: 1px dashed var(--msg-border); color: var(--msg-muted); font-style: italic; }
    .msg-meta { font-size: .72rem; margin-top: 4px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .msg-files { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
    .msg-files img { max-width: 160px; max-height: 120px; border-radius: 8px; display: block; }
    .msg-file { display: inline-flex; gap: 4px; align-items: center; padding: 4px 8px; border-radius: 8px; background: rgba(0,0,0,.06); font-size: .8rem; }
    .msg-divider { text-align: center; font-size: .75rem; color: var(--msg-accent); margin: 4px 0; }
    .msg-composer { border-top: 1px solid var(--msg-border); padding: 12px 16px; }
    .msg-composer textarea { width: 100%; resize: vertical; min-height: 64px; border: 1px solid var(--msg-border); border-radius: 10px; padding: 8px 10px; }
    .msg-composer-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
    .msg-note { padding: 10px 16px; background: #fff8e6; border-bottom: 1px solid #f3e2b3; font-size: .85rem; }
    .msg-readonly { padding: 14px 16px; border-top: 1px solid var(--msg-border); color: var(--msg-muted); text-align: center; }
    .msg-card { border: 1px solid var(--msg-border); border-radius: 12px; padding: 14px 16px; background: var(--msg-bg); margin-bottom: 12px; }
    .msg-actions { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .msg-actions form { margin: 0; }
    .msg-btn-link { background: none; border: 0; padding: 0; color: inherit; text-decoration: underline; font-size: inherit; cursor: pointer; }
    details.msg-menu summary { cursor: pointer; list-style: none; }
    details.msg-menu summary::-webkit-details-marker { display: none; }
    details.msg-menu[open] .msg-menu-body { display: block; }
    .msg-menu-body { position: absolute; right: 16px; z-index: 20; background: #fff; border: 1px solid var(--msg-border); border-radius: 10px; padding: 10px; min-width: 240px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    .msg-menu-body form { margin-bottom: 8px; }
    /* The contractor layout does not load landlord-workspace.css, so give the primary button its look here too. */
    .msg-shell .btn.lw-btn-primary { background: var(--lw-accent, #0f173a); border-color: var(--lw-accent, #0f173a); color: #fff; font-weight: 600; border-radius: 10px; }
    .msg-shell .btn.lw-btn-primary:hover, .msg-shell .btn.lw-btn-primary:focus { background: #1a2454; border-color: #1a2454; color: #fff; }
    .msg-shell .msg-back { display: none !important; }
    /* One pane at a time when the content area is narrow (phone, or a desktop with little room). */
    @container (max-width: 700px) {
        .msg-layout { grid-template-columns: minmax(0, 1fr); min-height: 0; border-radius: 10px; }
        .msg-list { border-right: 0; max-height: none; }
        .msg-layout.has-thread .msg-list { display: none; }
        .msg-layout:not(.has-thread) .msg-pane { display: none; }
        .msg-shell .msg-back { display: inline-flex !important; }
        .msg-thread { max-height: 60vh; }
        .msg-bubble { max-width: 90%; }
    }
    @media (max-width: 767.98px) {
        .msg-shell { padding: 0 8px 90px; }
    }
</style>
@endonce
