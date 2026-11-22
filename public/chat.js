const form = document.querySelector('#study-chat');
if (form) {
  const answer = document.querySelector('#chat-answer');
  form.querySelectorAll('[data-prompt]').forEach(chip => chip.addEventListener('click', () => {
    if (form.querySelector('[type=submit]').disabled) return;
    form.elements.question.value = chip.dataset.prompt;
    form.requestSubmit();
  }));
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('[type=submit]');
    const data = new FormData(form);
    button.disabled = true;
    answer.textContent = 'Consultando sua agenda…';
    try {
      const response = await fetch('assistente.php', {method: 'POST', body: data});
      const result = await response.json();
      answer.textContent = result.resposta;
    } catch {
      answer.textContent = 'Falha de conexão com o TaskFlow. Tente novamente.';
    } finally {
      button.disabled = false;
    }
  });
}
