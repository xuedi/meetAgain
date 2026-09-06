/**
 * Ballot Terms - ask how a vote should run, without leaving the form
 *
 * Two ways in, because a vote is started two ways.
 *
 * A select trigger (data-trigger + data-value): when the select takes the sentinel value the modal
 * opens, confirming leaves it there and cancelling puts the select back, so an accidental pick
 * never survives. Nothing is posted - the fields live in the host form and ride along on save.
 *
 * A button trigger (data-ballot-terms-open on any element): the modal opens on click, the button's
 * data-ballot-field is copied into the host form's data-ballot-terms-field input, and confirming
 * submits that form. Here the vote is the only thing being asked for, so there is nothing to wait
 * for.
 *
 * Loaded in:  templates/admin/event/edit.html.twig, new.html.twig, review/proposals.html.twig
 * Used by:    templates/_components/ballot_terms_modal.html.twig
 */

document.addEventListener('DOMContentLoaded', function () {
    const buttonDriven = new Set();

    document.querySelectorAll('[data-ballot-terms-open]').forEach(function (button) {
        const modal = document.getElementById(button.dataset.ballotTermsOpen);
        if (!modal) return;

        buttonDriven.add(modal);
        button.addEventListener('click', function () {
            const form = modal.closest('form');
            const field = form && form.querySelector('[data-ballot-terms-field]');
            if (field) field.value = button.dataset.ballotField || '';
            modal.classList.add('is-active');
            const first = modal.querySelector('input:not([type=hidden]), select, textarea');
            if (first) first.focus();
        });
    });

    buttonDriven.forEach(function (modal) {
        function close() {
            modal.classList.remove('is-active');
        }

        modal.querySelectorAll('[data-ballot-terms-confirm]').forEach(function (node) {
            node.addEventListener('click', function () {
                const form = modal.closest('form');
                if (form) form.submit();
            });
        });

        modal.querySelectorAll('[data-ballot-terms-cancel], [data-ballot-terms-close]').forEach(function (node) {
            node.addEventListener('click', close);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-active')) close();
        });
    });

    document.querySelectorAll('[data-ballot-terms]').forEach(function (modal) {
        const trigger = document.getElementById(modal.dataset.trigger);
        if (!trigger) return;

        const sentinel = modal.dataset.value;
        let previousValue = trigger.value === sentinel ? '' : trigger.value;

        function open() {
            modal.classList.add('is-active');
            const first = modal.querySelector('input, select, textarea');
            if (first) first.focus();
        }

        function close() {
            modal.classList.remove('is-active');
        }

        function cancel() {
            trigger.value = previousValue;
            close();
        }

        trigger.addEventListener('change', function () {
            if (trigger.value === sentinel) {
                open();
                return;
            }
            previousValue = trigger.value;
        });

        modal.querySelectorAll('[data-ballot-terms-confirm]').forEach(function (node) {
            node.addEventListener('click', close);
        });

        modal.querySelectorAll('[data-ballot-terms-cancel], [data-ballot-terms-close]').forEach(function (node) {
            node.addEventListener('click', cancel);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-active')) cancel();
        });
    });
});
