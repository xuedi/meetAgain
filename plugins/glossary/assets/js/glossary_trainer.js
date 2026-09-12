/**
 * Glossary Trainer -- Instant reveal and keyboard grading for the flashcard trainer
 *
 * Space reveals the answer in place instead of the extra request the plain reveal link makes, and the
 * number keys press the matching grade or choice button. Without JavaScript the reveal link and the
 * buttons work as ordinary requests, so this only layers shortcuts on top.
 *
 * Loaded in:  base.html.twig via Plugin::getJavascripts() (all pages, active plugin only)
 * Used by:    [data-trainer-card] with [data-trainer-reveal], [data-trainer-answer] and [data-trainer-key]
 *             (plugins/glossary/templates/trainer/card.html.twig)
 */
document.addEventListener('DOMContentLoaded', () => {
    const card = document.querySelector('[data-trainer-card]');
    if (!card) {
        return;
    }

    const reveal = card.querySelector('[data-trainer-reveal]');
    const answer = card.querySelector('[data-trainer-answer]');

    const showAnswer = () => {
        if (answer) {
            answer.classList.remove('is-hidden');
        }
        if (reveal) {
            reveal.classList.add('is-hidden');
        }
    };

    if (reveal) {
        reveal.addEventListener('click', (event) => {
            event.preventDefault();
            showAnswer();
        });
    }

    document.addEventListener('keydown', (event) => {
        const target = event.target;
        const typing = target instanceof HTMLElement
            && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
        if (typing || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        if (event.key === ' ' && reveal && !reveal.classList.contains('is-hidden')) {
            event.preventDefault();
            showAnswer();
            return;
        }

        const button = card.querySelector(`[data-trainer-key="${event.key}"]`);
        if (button && !button.closest('.is-hidden')) {
            event.preventDefault();
            button.click();
        }
    });
});
