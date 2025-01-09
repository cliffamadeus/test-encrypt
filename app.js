
const apiUrl = 'api.php';
const apiToken = 'sample-api-token';

async function fetchItems() {
    const response = await fetch(apiUrl, {
        headers: { 'Authorization': `Bearer ${apiToken}` }
    });
    const items = await response.json();
    itemList.innerHTML = '';
    items.forEach(item => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        li.innerHTML = `
            <span>
                <strong>${item.name}</strong>: ${item.data}
            </span>
            <div>
                <button class="btn btn-sm btn-warning me-2" onclick="editItem(${item.id}, '${item.name}', '${item.data}')">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteItem(${item.id})">Delete</button>
            </div>
        `;
        itemList.appendChild(li);
    });
}

async function addItem(e) {
    e.preventDefault();
    const name = document.getElementById('name').value;
    const data = document.getElementById('data').value;

    await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, data })
    });

    document.getElementById('itemForm').reset();
    fetchItems();
}

async function editItem(id, name, data) {
    const newName = prompt('Edit Name', name);
    const newData = prompt('Edit Data', data);

    if (newName && newData) {
        await fetch(apiUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, name: newName, data: newData })
        });
        fetchItems();
    }
}

async function deleteItem(id) {
    if (confirm('Are you sure?')) {
        await fetch(`${apiUrl}?id=${id}`, { method: 'DELETE' });
        fetchItems();
    }
}

document.getElementById('itemForm').addEventListener('submit', addItem);
fetchItems();