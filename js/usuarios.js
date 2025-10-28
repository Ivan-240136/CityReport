$(document).ready(function() {
    loadUsers();

    // Manejar envío del formulario
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        const userData = {
            id: $('#userId').val(),
            username: $('#username').val(),
            password: $('#password').val(),
            role: $('#role').val()
        };

        saveUser(userData);
    });

    // Limpiar formulario al hacer reset
    $('#userForm').on('reset', function() {
        $('#userId').val('');
    });
});

function loadUsers() {
    $.ajax({
        url: 'api/users_get.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayUsers(response.users);
            } else {
                alert('Error al cargar usuarios: ' + (response.message || 'Error desconocido'));
            }
        },
        error: function() {
            alert('Error al conectar con el servidor');
        }
    });
}

function displayUsers(users) {
    const tbody = $('#usersTable tbody');
    tbody.empty();

    users.forEach(user => {
        const row = `
            <tr>
                <td>${user.username}</td>
                <td>${user.role}</td>
                <td>
                    <button onclick="editUser(${user.id})" class="edit-btn">Editar</button>
                    <button onclick="deleteUser(${user.id})" class="delete-btn">Eliminar</button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function editUser(id) {
    $.ajax({
        url: 'api/users_get.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                const user = response.user;
                $('#userId').val(user.id);
                $('#username').val(user.username);
                $('#password').val('');
                $('#role').val(user.role);
            } else {
                alert('Error al cargar usuario: ' + (response.message || 'Error desconocido'));
            }
        },
        error: function() {
            alert('Error al conectar con el servidor');
        }
    });
}

function deleteUser(id) {
    if (confirm('¿Está seguro de que desea eliminar este usuario?')) {
        $.ajax({
            url: 'api/users_update.php',
            type: 'POST',
            data: {
                id: id,
                action: 'delete'
            },
            success: function(response) {
                if (response.success) {
                    loadUsers();
                } else {
                    alert('Error al eliminar usuario: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function() {
                alert('Error al conectar con el servidor');
            }
        });
    }
}

function saveUser(userData) {
    $.ajax({
        url: 'api/users_update.php',
        type: 'POST',
        data: userData,
        success: function(response) {
            if (response.success) {
                $('#userForm')[0].reset();
                loadUsers();
            } else {
                alert('Error al guardar usuario: ' + (response.message || 'Error desconocido'));
            }
        },
        error: function() {
            alert('Error al conectar con el servidor');
        }
    });
}