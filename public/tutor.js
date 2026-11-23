const tutor = document.querySelector('#tutor-form');
const log = document.querySelector('#tutor-dialog');
const turns = [];
tutor.addEventListener('submit', async event => {
  event.preventDefault();
  const submit = tutor.querySelector('button');
  if (submit.disabled) return;
  const payload = new FormData(tutor);
  payload.set('history', JSON.stringify(turns.slice(-4)));
  const status = document.createElement('p');
  status.textContent = 'Preparando a resposta…';
  log.append(status);
  submit.disabled = true;
  try {
    const response = await fetch('assistente.php', {method:'POST',body:payload});
    const data = await response.json();
    if (!response.ok) throw new Error(data.resposta || 'Falha na consulta.');
    if (data.prompt) {
      document.querySelector('#tutor-prompt').value = data.prompt;
      document.querySelector('#handoff').hidden = false;
      status.remove();
    } else {
      const question = payload.get('goal') + ' · ' + payload.get('question');
      status.textContent = question;
      const answer = document.createElement('p');
      answer.style.whiteSpace = 'pre-wrap';
      answer.textContent = data.resposta;
      log.append(answer);
      turns.push({question:question.slice(0,6000),answer:data.resposta.slice(0,6000)});
      tutor.elements.question.value = '';
    }
  } catch(error) { status.textContent = error.message; }
  finally { submit.disabled = false; }
});
document.querySelector('#copy-prompt').addEventListener('click', async event => {
  const prompt = document.querySelector('#tutor-prompt');
  try { await navigator.clipboard.writeText(prompt.value); event.target.textContent='Copiado'; }
  catch { prompt.focus();prompt.select();event.target.textContent='Use Ctrl+C para copiar'; }
});
