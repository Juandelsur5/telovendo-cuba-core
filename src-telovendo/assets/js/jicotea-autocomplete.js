/**
 * Jicotea Autocomplete: Sistema de sugerencias para búsqueda de direcciones
 * Consulta municipios-cuba.json para sugerir calles, esquinas y lugares famosos
 */

class JicoteaAutocomplete {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            minLength: 2,
            delay: 300,
            ...options
        };
        this.data = null;
        this.suggestionsContainer = null;
        this.init();
    }

    async init() {
        // Cargar datos del JSON
        await this.loadMunicipiosData();
        
        // Crear contenedor de sugerencias
        this.createSuggestionsContainer();
        
        // Event listeners
        this.input.addEventListener('input', this.debounce(() => {
            this.handleInput();
        }, this.options.delay));
        
        this.input.addEventListener('blur', () => {
            // Delay para permitir clicks en sugerencias
            setTimeout(() => {
                this.hideSuggestions();
            }, 200);
        });
    }

    async loadMunicipiosData() {
        try {
            const response = await fetch('/src-telovendo/custom-modules/municipios-cuba.json');
            if (response.ok) {
                this.data = await response.json();
            }
        } catch (error) {
            // Silencioso
        }
    }

    createSuggestionsContainer() {
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.className = 'jicotea-autocomplete-suggestions';
        this.suggestionsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 10000;
            display: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        `;
        
        this.input.parentElement.style.position = 'relative';
        this.input.parentElement.appendChild(this.suggestionsContainer);
    }

    handleInput() {
        const query = this.input.value.trim();
        
        if (query.length < this.options.minLength) {
            this.hideSuggestions();
            return;
        }
        
        const suggestions = this.searchSuggestions(query);
        
        if (suggestions.length > 0) {
            this.showSuggestions(suggestions);
        } else {
            this.hideSuggestions();
        }
    }

    searchSuggestions(query) {
        if (!this.data) return [];
        
        const queryLower = query.toLowerCase();
        const suggestions = [];
        
        // Buscar en provincias
        for (const provincia in this.data.provincias) {
            if (provincia.toLowerCase().includes(queryLower)) {
                suggestions.push({
                    text: provincia,
                    type: 'provincia',
                    display: `📍 ${provincia}`
                });
            }
            
            // Buscar en municipios
            for (const municipio in this.data.provincias[provincia].municipios) {
                if (municipio.toLowerCase().includes(queryLower)) {
                    suggestions.push({
                        text: municipio,
                        type: 'municipio',
                        display: `🏘️ ${municipio}, ${provincia}`
                    });
                }
                
                const municipioData = this.data.provincias[provincia].municipios[municipio];
                
                // Buscar en calles famosas
                (municipioData.calles_famosas || []).forEach(calle => {
                    if (calle.toLowerCase().includes(queryLower)) {
                        suggestions.push({
                            text: calle,
                            type: 'calle',
                            display: `🛣️ ${calle}, ${municipio}`
                        });
                    }
                });
                
                // Buscar en esquinas famosas
                (municipioData.esquinas_famosas || []).forEach(esquina => {
                    if (esquina.toLowerCase().includes(queryLower)) {
                        suggestions.push({
                            text: esquina,
                            type: 'esquina',
                            display: `📍 ${esquina}, ${municipio}`
                        });
                    }
                });
                
                // Buscar en barrios
                (municipioData.barrios || []).forEach(barrio => {
                    if (barrio.toLowerCase().includes(queryLower)) {
                        suggestions.push({
                            text: barrio,
                            type: 'barrio',
                            display: `🏠 ${barrio}, ${municipio}`
                        });
                    }
                });
            }
        }
        
        // Buscar en lugares famosos
        for (const provincia in this.data.lugares_famosos) {
            this.data.lugares_famosos[provincia].forEach(lugar => {
                if (lugar.toLowerCase().includes(queryLower)) {
                    suggestions.push({
                        text: lugar,
                        type: 'lugar',
                        display: `⭐ ${lugar}, ${provincia}`
                    });
                }
            });
        }
        
        // Limitar a 10 sugerencias
        return suggestions.slice(0, 10);
    }

    showSuggestions(suggestions) {
        this.suggestionsContainer.innerHTML = '';
        
        suggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            item.className = 'jicotea-autocomplete-item';
            item.style.cssText = `
                padding: 12px 16px;
                cursor: pointer;
                transition: background 0.2s;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            `;
            item.textContent = suggestion.display;
            
            item.addEventListener('mouseenter', () => {
                item.style.background = 'rgba(0, 210, 255, 0.1)';
            });
            
            item.addEventListener('mouseleave', () => {
                item.style.background = 'transparent';
            });
            
            item.addEventListener('click', () => {
                this.input.value = suggestion.text;
                this.hideSuggestions();
                this.input.dispatchEvent(new Event('change'));
            });
            
            this.suggestionsContainer.appendChild(item);
        });
        
        this.suggestionsContainer.style.display = 'block';
    }

    hideSuggestions() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.style.display = 'none';
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Exportar para uso global
window.JicoteaAutocomplete = JicoteaAutocomplete;

