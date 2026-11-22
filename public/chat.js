const form = document.querySelector('#study-chat');
if (form) {
  const answer = document.querySelector('#chat-answer');
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('button');
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
