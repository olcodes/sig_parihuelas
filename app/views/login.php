<!-- Bootstrap Icons CDN para los íconos -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="container-fluid d-flex align-items-center justify-content-center bg-light" style="min-height:100vh;">
    <div class="row w-100 shadow-lg rounded-4 overflow-hidden" style="max-width:900px; background:#fff;">
        <!-- Lado izquierdo: imagen o color -->
    <div class="col-md-6 d-none d-md-flex flex-column justify-content-center align-items-center p-0" style="background: linear-gradient(135deg, #f8fafc 60%, #e3eaf6 100%);">
            <div class="text-white text-center px-4">
                <img src="<?= BASE_URL ?>/img/Logo-Lavoro-1536x442.png" alt="Logo Lavoro" style="max-width: 220px; width: 100%; margin-bottom: 30px; background: transparent; border-radius: 0; box-shadow: none;">
    <h2 class="fw-bold mb-3" style="letter-spacing:1px; color:#1a237e; text-shadow:0 2px 8px rgba(0,0,0,0.06);">Bienvenido a Lavoro-ERP</h2>
    <p class="mb-0" style="opacity:0.92; color:#263159; text-shadow:0 1px 4px rgba(0,0,0,0.06);">Sistema de gestión para automatización y mejora de los procesos.<br>Por favor, inicia sesión para continuar.</p>
            </div>
        </div>
        <!-- Lado derecho: formulario login -->
        <div class="col-12 col-md-6 bg-white d-flex flex-column justify-content-center align-items-center py-5 px-4">
            <div class="w-100" style="max-width:350px;">
                <h3 class="mb-4 text-center fw-bold" style="letter-spacing:1px;">Iniciar sesión</h3>
                <?php if (!empty($timeoutMsg)): ?>
                    <div class="alert alert-warning text-center py-2">
                        <i class="bi bi-clock-history me-1"></i><?= htmlspecialchars($timeoutMsg) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= app_url('login/auth') ?>" autocomplete="off" class="needs-validation" novalidate>
                    <div class="mb-3 position-relative">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control login-input" id="username" name="username" required value="<?= htmlspecialchars($username ?? '') ?>" autofocus>
                        <div class="invalid-feedback">Ingrese su usuario.</div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control login-input" id="password" name="password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1" style="border-radius:0 .375rem .375rem 0;">
                                <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Ingrese su contraseña.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mt-2">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .login-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.15);
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .login-input:hover {
        border-color: #0056b3;
        transition: border-color 0.2s;
    }
    .toggle-password:hover {
        background: #e9ecef;
        color: #007bff;
        transition: background 0.2s, color 0.2s;
    }
</style>

<script>
// Mostrar/ocultar contraseña
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    if (toggleBtn && passwordInput && icon) {
        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            icon.className = type === 'password' ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }

    // Validación Bootstrap
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>
