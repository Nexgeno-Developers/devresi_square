<style>
.rs-record-page { max-width: none; }
.rs-workspace { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(300px, 0.75fr); gap: 20px; align-items: start; }
.rs-rail { position: sticky; top: 84px; }
.rs-rail .rs-facts { grid-template-columns: 1fr 1fr; }
.rs-rail .rs-section:first-child { padding-top: 0; }
.rs-rail-title { margin: 0; font-size: 15px; font-weight: 650; }
.rs-rail-note { margin: 4px 0 14px; color: #5b6573; font-size: 13px; }
.rs-rail-list { display: grid; gap: 12px; margin: 0 0 16px; }
.rs-rail-list div { display: grid; gap: 2px; }
.rs-rail-list dt { font-size: 12px; font-weight: 600; color: #5b6573; }
.rs-rail-list dd { margin: 0; font-size: 14px; font-weight: 600; color: #0b1220; }
.rs-rail-list dd.is-empty { font-weight: 500; color: #8b95a5; }
.rs-sheet, .rs-sheet-card { max-width: none; }
.rs-sheet-card .card-body { padding: 8px 28px 28px; }
.rs-section { padding: 22px 0 4px; border-top: 1px solid #e6e9ef; }
.rs-section:first-child { border-top: 0; padding-top: 4px; }
.rs-section-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 14px; }
.rs-section-head h2 { margin: 0; font-size: 15px; font-weight: 650; letter-spacing: 0; }
.rs-section-head p { margin: 4px 0 0; max-width: 48ch; color: #5b6573; font-size: 13px; font-weight: 400; line-height: 1.4; }
.rs-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 16px 18px; align-items: start; }
.rs-span-2 { grid-column: span 2; }
.rs-span-3 { grid-column: span 3; }
.rs-span-4 { grid-column: span 4; }
.rs-span-6 { grid-column: span 6; }
.rs-field { display: flex; flex-direction: column; gap: 6px; margin: 0; font-size: 13px; font-weight: 600; color: #0b1220; }
.rs-field .form-control,
.rs-field .form-select,
.rs-field .select2-container { width: 100% !important; max-width: 100%; font-weight: 400; }
.rs-field .form-text { margin: 0; font-weight: 400; }
.rs-check { display: flex; gap: 8px; align-items: flex-start; margin: 0; font-size: 13px; font-weight: 500; }
.rs-check .form-text { display: block; margin-top: 2px; font-weight: 400; }
.rs-actions { display: flex; justify-content: flex-end; gap: 8px; padding-top: 18px; }
.rs-facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px 24px; }
.rs-facts .is-wide { grid-column: 1 / -1; }
.rs-facts > div > span { display: block; font-size: 12px; font-weight: 600; color: #5b6573; }
.rs-facts > div > strong { display: block; margin-top: 4px; font-size: 15px; font-weight: 600; color: #0b1220; }
.rs-people { display: flex; flex-direction: column; gap: 8px; }
.rs-person { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; padding: 10px 12px; border: 1px solid #e6e9ef; border-radius: 10px; }
.rs-person p { margin: 0; }
.rs-person small { color: #5b6573; }
.rs-lead { display: flex; flex-wrap: wrap; gap: 10px 18px; align-items: center; padding: 10px 12px; background: #f6f8fb; border-radius: 10px; }
.rs-lead > span { font-size: 13px; font-weight: 600; }
.rs-lead .form-check { display: flex; gap: 6px; align-items: center; margin: 0; }
@media (max-width: 1100px) {
    .rs-workspace { grid-template-columns: 1fr; }
    .rs-rail { position: static; }
}
@media (max-width: 800px) {
    .rs-grid, .rs-facts { grid-template-columns: 1fr; }
    .rs-span-2, .rs-span-3, .rs-span-4, .rs-span-6 { grid-column: auto; }
}
</style>
<script>
(function () {
    function textOf(selector) {
        var el = document.querySelector(selector);
        if (!el) return '';
        if (el.tagName === 'SELECT') {
            if (!el.value && !el.multiple) return '';
            if (el.multiple) {
                return Array.prototype.map.call(el.selectedOptions || [], function (option) {
                    return (option.text || '').trim();
                }).filter(Boolean).join(', ');
            }
            var chosen = el.selectedOptions && el.selectedOptions[0];
            return chosen ? (chosen.text || '').trim() : '';
        }
        if (el.dataset && el.dataset.propertyLabel) return el.dataset.propertyLabel;
        return (el.value || '').trim();
    }

    function prettyDate(value) {
        var parts = (value || '').split('-');
        if (parts.length !== 3) return value || '';
        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function money(value) {
        var amount = Number(value);
        if (value === '' || Number.isNaN(amount)) return '';
        return '£' + amount.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setRail(key, value, empty) {
        var node = document.querySelector('[data-rail="' + key + '"]');
        if (!node) return;
        node.textContent = value || empty;
        node.classList.toggle('is-empty', !value);
    }

    function paintTenancyRail() {
        if (!document.querySelector('[data-rail]')) return;
        var property = document.querySelector('#property_id');
        var propertyText = '';
        if (property && property.tagName === 'INPUT') {
            propertyText = property.dataset.propertyLabel || '';
        } else {
            propertyText = textOf('#property_id');
        }
        var moveIn = textOf('#tenancies-move_in');
        var moveOut = textOf('#tenancies-move_out');
        var term = textOf('#tenancies-term_months');
        var dates = '';
        if (moveIn || moveOut) {
            dates = prettyDate(moveIn) || 'Move in not set';
            dates += ' → ' + (prettyDate(moveOut) || 'Move out not set');
            if (term) dates += ' · ' + term + ' months';
        } else if (term) {
            dates = term + ' months';
        }
        var rent = money(textOf('#tenancies-rent'));
        var frequency = textOf('#tenancies-frequency');
        var due = textOf('#tenancies-rent-due-day');
        if (rent && frequency) rent += ' · ' + frequency;
        if (rent && due) rent += ' · day ' + due;
        var scheme = textOf('#depositScheme');
        var reference = textOf('#referenceNumber');
        if (scheme && reference) scheme += ' · ' + reference;

        setRail('property', propertyText, 'Choose a property');
        setRail('tenants', textOf('#tenant_id'), 'No tenants yet');
        setRail('dates', dates, 'Set the move-in date');
        setRail('rent', rent, 'Set the rent');
        setRail('deposit', money(textOf('#tenancies-deposit')), 'Set the deposit');
        setRail('scheme', scheme, 'Add this after the let is saved');
    }

    document.addEventListener('input', paintTenancyRail);
    document.addEventListener('change', paintTenancyRail);
    document.addEventListener('click', function (event) {
        var rail = document.querySelector('.rs-rail');
        if (!rail) return;
        if (event.target.closest('#addUserBtn')) rail.hidden = true;
        if (event.target.closest('#backToMainForm')) rail.hidden = false;
    });

    function bootTenancyRail() {
        paintTenancyRail();
        if (window.jQuery) {
            window.jQuery(document).on('change select2:select select2:unselect', '#property_id, #tenant_id, #tenancies-frequency, #depositScheme', paintTenancyRail);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootTenancyRail);
    } else {
        bootTenancyRail();
    }
})();
</script>
