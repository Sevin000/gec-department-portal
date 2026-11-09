'use strict';
const $ = selector => document.querySelector(selector);
const API = '/api';
let searchTimer = null;
let facultyCache = [];

const create = (tag, className, text) => { const el = document.createElement(tag); if (className) el.className = className; if (text !== undefined) el.textContent = text; return el; };
function setAppStatus(message, error = false) { const el = $('#app-status'); el.textContent = message; el.classList.toggle('error', error); }
function setAdminStatus(message, error = false) { const el = $('#admin-status'); el.textContent = message; el.classList.toggle('error', error); }
async function apiFetch(path, options = {}) {
  const response = await fetch(`${API}${path}`, { credentials: 'same-origin', ...options, headers: { 'Accept': 'application/json', ...(options.body ? { 'Content-Type': 'application/json' } : {}), ...(options.headers || {}) } });
  const data = await response.json().catch(() => ({ success: false, error: 'Server returned an invalid JSON response.' }));
  if (!response.ok) throw new Error(data.error || `Request failed (${response.status})`);
  return data;
}
function addTags(parent, values) { const wrap = create('div', 'tag-list'); values.forEach(value => wrap.append(create('span', 'tag', value))); parent.append(wrap); }
function renderCards(selector, values, label) { const root = $(selector); root.replaceChildren(); values.forEach((value, i) => { const card = create('article', 'card'); card.append(create('span', 'eyebrow', `${label} ${String(i + 1).padStart(2, '0')}`), create('h3', '', value)); root.append(card); }); }
function renderFaculty(items) {
  facultyCache = items;
  const root = $('#faculty-list'); root.replaceChildren();
  items.forEach(person => {
    const card = create('article', 'card');
    const initials = person.name.replace(/^Prof\.?\s*/i, '').split(/\s+/).map(x => x[0]).slice(0,2).join('').toUpperCase();
    card.append(create('div', 'avatar', initials), create('h3', '', person.name), create('p', '', person.designation));
    addTags(card, person.specialization || []);
    const edit = create('button', 'button button-light edit-button', 'Edit'); edit.type = 'button'; edit.addEventListener('click', () => beginEdit(person)); card.append(edit);
    root.append(card);
  });
  $('#result-count').textContent = `${items.length} faculty member${items.length === 1 ? '' : 's'} found`;
  $('#no-results').hidden = items.length !== 0;
}
async function loadDepartment() {
  setAppStatus('Loading department information from MySQL…');
  try {
    const [deptData, facultyData] = await Promise.all([apiFetch('/department'), apiFetch('/items')]);
    const dept = deptData.department; const items = facultyData.items;
    $('#department-info').replaceChildren(create('p', 'intro', dept.description));
    const stats = [[items.length,'Faculty members'],[dept.programs.length,'Academic programmes'],[dept.facilities.length,'Facilities']];
    $('#stats').replaceChildren(); stats.forEach(([n,label]) => { const card=create('div','stat'); card.append(create('strong','',String(n)),create('span','',label)); $('#stats').append(card); });
    renderCards('#programmes-list', dept.programs, 'PROGRAMME'); renderCards('#facilities-list', dept.facilities, 'FACILITY'); addTags($('#research-list'), dept.researchAreas);
    renderFaculty(items); populateSpecializations(items);
    $('#faculty-search').disabled = false; $('#specialization-filter').disabled = false;
    setAppStatus('Department information loaded from the MySQL database.');
  } catch (error) { setAppStatus(error.message, true); }
}
function populateSpecializations(items) {
  const select = $('#specialization-filter'); const previous = select.value; select.replaceChildren(new Option('All specializations',''));
  [...new Set(items.flatMap(item => item.specialization || []))].sort().forEach(value => select.add(new Option(value,value)));
  select.value = previous;
}
async function searchFaculty() {
  const q = $('#faculty-search').value.trim(); const spec = $('#specialization-filter').value;
  try {
    const data = await apiFetch(`/items${q ? `?q=${encodeURIComponent(q)}` : ''}`);
    const items = spec ? data.items.filter(item => item.specialization.includes(spec)) : data.items;
    renderFaculty(items);
  } catch (error) { setAdminStatus(error.message, true); }
}
function beginEdit(person) {
  $('#faculty-id').value = person.id; $('#faculty-name').value = person.name; $('#faculty-designation').value = person.designation;
  $('#faculty-specialization').value = person.specialization.join(', '); $('#form-title').textContent = 'Update faculty member'; $('#cancel-edit').hidden = false;
  $('#admin').scrollIntoView({behavior:'smooth'});
}
function resetFacultyForm() { $('#faculty-form').reset(); $('#faculty-id').value = ''; $('#form-title').textContent = 'Add faculty member'; $('#cancel-edit').hidden = true; }
async function refreshFaculty() { const data = await apiFetch('/items'); renderFaculty(data.items); populateSpecializations(data.items); }
async function checkSession() {
  const data = await apiFetch('/session');
  $('#login-panel').hidden = data.authenticated; $('#editor-panel').hidden = !data.authenticated;
  document.querySelectorAll('.edit-button').forEach(button => button.hidden = !data.authenticated);
}
$('#faculty-search').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(searchFaculty, 250); });
$('#specialization-filter').addEventListener('change', searchFaculty);
$('#login-form').addEventListener('submit', async event => {
  event.preventDefault(); setAdminStatus('Signing in…');
  try { const data = await apiFetch('/login',{method:'POST',body:JSON.stringify({username:$('#username').value,password:$('#password').value})}); $('#login-panel').hidden=true; $('#editor-panel').hidden=false; setAdminStatus(data.message); await refreshFaculty(); await checkSession(); }
  catch(error) { setAdminStatus(error.message,true); }
});
$('#logout-button').addEventListener('click', async () => { try { await apiFetch('/logout',{method:'POST',body:'{}'}); $('#editor-panel').hidden=true; $('#login-panel').hidden=false; resetFacultyForm(); setAdminStatus('You have logged out.'); await refreshFaculty(); } catch(error) { setAdminStatus(error.message,true); } });
$('#cancel-edit').addEventListener('click', resetFacultyForm);
$('#faculty-form').addEventListener('submit', async event => {
  event.preventDefault();
  const id = $('#faculty-id').value;
  const payload = {name:$('#faculty-name').value.trim(),designation:$('#faculty-designation').value.trim(),specialization:$('#faculty-specialization').value.split(',').map(x=>x.trim()).filter(Boolean)};
  try {
    const data = await apiFetch(id ? `/items/${encodeURIComponent(id)}` : '/items',{method:id?'PUT':'POST',body:JSON.stringify(payload)});
    setAdminStatus(data.message); resetFacultyForm(); $('#faculty-search').value=''; $('#specialization-filter').value=''; await refreshFaculty();
  } catch(error) { setAdminStatus(error.message,true); }
});
document.addEventListener('DOMContentLoaded', async () => { $('#year').textContent = new Date().getFullYear(); await loadDepartment(); try { await checkSession(); } catch (_) { /* Public browsing remains available if session check fails. */ } });
