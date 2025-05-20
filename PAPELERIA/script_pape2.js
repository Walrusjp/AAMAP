document.querySelectorAll('.view-tabs .nav-link').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remover clase active de todas las pestañas
        document.querySelectorAll('.view-tabs .nav-link').forEach(t => {
            t.classList.remove('active');
        });
        
        // Añadir clase active a la pestaña clickeada
        this.classList.add('active');
        
        // Obtener el tipo de vista
        const view = this.getAttribute('data-view');
        
        // Actualizar la URL sin recargar la página
        const url = new URL(window.location.href);
        url.searchParams.set('view', view);
        window.history.pushState({}, '', url);
        
        // Recargar los productos (simulando recarga)
        location.reload();
    });
});

// Marcar la pestaña activa al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentView = urlParams.get('view') || 'all';
    
    document.querySelectorAll('.view-tabs .nav-link').forEach(tab => {
        if (tab.getAttribute('data-view') === currentView) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
});