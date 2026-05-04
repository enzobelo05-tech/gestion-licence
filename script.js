document.addEventListener('DOMContentLoaded', function () {

    /* ============================================================
     *  Gestion générique des modales (popUp + overlay)
     * ============================================================ */
    const overlay = document.getElementById('overlay');
    const popUps  = document.querySelectorAll('.popUp');

    function fermerToutesPopUps() {
        popUps.forEach(p => p.style.display = 'none');
        if (overlay) overlay.style.display = 'none';
    }

    function ouvrirPopUp(popUp) {
        if (!popUp) return;
        fermerToutesPopUps();
        popUp.style.display = 'flex';
        if (overlay) overlay.style.display = 'block';
    }

    /* Fermeture (croix, bouton "Annuler", overlay, échap) */
    document.querySelectorAll('.close-btn-popUp, .cancel-btn-popUp')
        .forEach(el => el.addEventListener('click', fermerToutesPopUps));
    if (overlay) overlay.addEventListener('click', fermerToutesPopUps);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') fermerToutesPopUps();
    });

    /* Boutons "Ajouter" historiques (corps enseignant, etc.) */
    const popUpDefaut = document.querySelector('.popUp');
    document.querySelectorAll('.addInstructor, .addInter').forEach(btn => {
        btn.addEventListener('click', () => ouvrirPopUp(popUpDefaut));
    });

    /* ============================================================
     *  Page liste interventions : ajout / modification / suppression
     * ============================================================ */
    const popUpAjout = document.getElementById('popUp-ajout');
    const popUpEdit  = document.getElementById('popUp-edit');
    const map        = window.MAP_MODULE_INTERVENANTS || {};

    /* TomSelect sur les <select multiple> d'intervenants (ajout + edit) */
    const tomSelects = new Map();
    if (typeof TomSelect !== 'undefined') {
        document.querySelectorAll('.select-intervenants').forEach(sel => {
            tomSelects.set(sel, new TomSelect(sel, {
                plugins: ['remove_button'],
                placeholder: 'Sélectionnez les intervenants',
                maxOptions: null,
            }));
        });
    }

    /* Filtrage des intervenants quand le module change */
    function filtrerIntervenants(form) {
        const moduleSelect       = form.querySelector('.select-module');
        const intervenantsSelect = form.querySelector('.select-intervenants');
        if (!moduleSelect || !intervenantsSelect) return;

        const ts        = tomSelects.get(intervenantsSelect);
        const moduleId  = moduleSelect.value;
        const autorises = (map[moduleId] || []).map(String);

        Array.from(intervenantsSelect.options).forEach(opt => {
            const ok = !moduleId || autorises.includes(opt.value);
            opt.disabled = !ok;
            opt.hidden   = !ok;
            if (!ok && ts && ts.items.includes(opt.value)) ts.removeItem(opt.value);
        });
        if (ts) ts.refreshOptions(false);
    }

    document.querySelectorAll('.form-intervention .select-module').forEach(sel => {
        sel.addEventListener('change', () => filtrerIntervenants(sel.closest('form')));
    });

    /* Ouverture du popUp Ajout */
    const btnOpenAjout = document.getElementById('btn-open-popUp');
    if (btnOpenAjout && popUpAjout) {
        btnOpenAjout.addEventListener('click', () => {
            const form = popUpAjout.querySelector('form.form-intervention');
            if (form) {
                form.reset();
                tomSelects.forEach((ts, sel) => {
                    if (form.contains(sel)) ts.clear();
                });
                filtrerIntervenants(form);
            }
            ouvrirPopUp(popUpAjout);
        });
    }

    /* Ouverture du popUp Edition à partir d'une ligne du tableau */
    document.querySelectorAll('.btn-edit-intervention').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!popUpEdit) return;
            const form = popUpEdit.querySelector('form.form-intervention');
            const d    = btn.dataset;

            form.querySelector('input[name="id"]').value          = d.id;
            form.querySelector('input[name="titre"]').value       = d.titre;
            form.querySelector('input[name="date_debut"]').value  = d.debut;
            form.querySelector('input[name="date_fin"]').value    = d.fin;
            form.querySelector('select[name="module_id"]').value  = d.module;
            form.querySelector('select[name="type_intervention_id"]').value = d.type;
            form.querySelector('input[name="visio"]').checked     = d.visio === '1';

            filtrerIntervenants(form);

            const intervenantsSelect = form.querySelector('.select-intervenants');
            const ids = (d.intervenants || '').split(',').filter(Boolean);
            const ts  = tomSelects.get(intervenantsSelect);
            if (ts) {
                ts.clear();
                ts.addItems(ids);
            }

            popUpEdit.querySelector('#form-delete input[name="id"]').value = d.id;

            ouvrirPopUp(popUpEdit);
        });
    });

    /* Bouton suppression */
    const btnDelete = document.getElementById('btn-delete-edit');
    if (btnDelete) {
        btnDelete.addEventListener('click', () => {
            if (!confirm("Supprimer définitivement cette intervention ?")) return;
            document.getElementById('form-delete').submit();
        });
    }

    /* ============================================================
     *  Validation client : durée <= 4h, fin > début
     * ============================================================ */
    document.querySelectorAll('.form-intervention').forEach(form => {
        form.addEventListener('submit', e => {
            const debut = form.querySelector('input[name="date_debut"]').value;
            const fin   = form.querySelector('input[name="date_fin"]').value;
            if (!debut || !fin) return;
            const dDebut = new Date(debut);
            const dFin   = new Date(fin);
            if (dFin <= dDebut) {
                e.preventDefault();
                alert("La date de fin doit être supérieure à la date de début.");
                return;
            }
            if ((dFin - dDebut) > 4 * 60 * 60 * 1000) {
                e.preventDefault();
                alert("Une intervention ne peut pas dépasser 4h.");
            }
        });
    });
});
