/**
 * JICOTEA-GENIO V1.0 - TE LO VENDO CUBA
 * CORE: Sistema de IA con personalidad cubana, humor hiper-realista y misticismo chistoso
 */

/* CORE JICOTEA-GENIO V1.0 - TE LO VENDO CUBA */
const JicoteaIA = {
    personalidad: "Extremadamente chistosa, mística y hospitalaria",
    medidas: { avatar: "200px", chat: "320x450px", bola: "60px" },
    historia: "Piratas, Arqueología, Religión y Cuentos de Cuba",
    regla_oro: "Preguntar SIEMPRE antes de seguir un cuento",
    
    hablar: function(contexto) {
        if(contexto === 'turista') {
            this.activarHumorMaximo();
            this.cerrarChatAutomatico(); // Deja a la Jicotea sola gesticulando
        }
    },
    
    activarHumorMaximo: function() {
        // Activar animaciones de humor
        const jicotea = document.querySelector('.jicotea-genio-ia');
        if (jicotea) {
            jicotea.classList.add('humor-activo');
        }
    },
    
    cerrarChatAutomatico: function() {
        if (jicoteaGenio) {
            jicoteaGenio.onSpeakStart();
        }
    }
};

class JicoteaGenio {
    constructor() {
        this.chatContainer = null;
        this.isOpen = false;
        this.messages = [];
        this.currentLanguage = this.detectLanguage();
        this.apiUrl = '/wp-json/jicotea/v1/properties';
        this.systemPrompt = this.getSystemPrompt();
        this.narrativeState = {
            isTellingStory: false,
            currentStory: null,
            storyPart: 0,
            waitingForConsent: false
        };
        this.knowledgeBase = this.initializeKnowledgeBase();
        // Estados: 'HABLANDO', 'CHAT_ABIERTO', 'MINIMIZADO'
        this.estadoActual = 'CHAT_ABIERTO';
        this.speakingTimeout = null;
        this.canReturnToChat = true;
        this.activeProperty = null; // Propiedad activa actual
        this.userType = 'tourist'; // 'tourist' o 'buyer'
        this.externalDataCache = {}; // Cache para datos externos
        this.init();
    }
    
    // Persistencia de Estados en sessionStorage
    saveState() {
        try {
            sessionStorage.setItem('jicotea_estado', this.estadoActual);
            sessionStorage.setItem('jicotea_minimizado', this.estadoActual === 'MINIMIZADO' ? 'true' : 'false');
        } catch (e) {
            // Silencioso si sessionStorage no está disponible
        }
    }
    
    loadState() {
        try {
            const estadoGuardado = sessionStorage.getItem('jicotea_estado');
            const minimizado = sessionStorage.getItem('jicotea_minimizado');
            if (minimizado === 'true' && estadoGuardado === 'MINIMIZADO') {
                this.estadoActual = 'MINIMIZADO';
                return true;
            }
        } catch (e) {
            // Silencioso
        }
        return false;
    }

    init() {
        try {
            // Cargar estado persistente
            const estadoCargado = this.loadState();
            
            // Crear estructura del chat con manejo de errores
            this.createChatStructure();
            
            // Event listeners con verificación de existencia
            const anchor = document.getElementById('jicotea-ia-anchor');
            if (anchor) {
                anchor.addEventListener('click', () => {
                    try {
                        if (this.estadoActual === 'MINIMIZADO' || this.estadoActual === 'HABLANDO') {
                            if (this.canReturnToChat || this.estadoActual === 'MINIMIZADO') {
                                this.abrirChat();
                            }
                        } else {
                            this.minimizarABola();
                        }
                        this.saveState();
                    } catch (e) {
                        // Error silencioso
                    }
                });
            } else {
                throw new Error('Elemento jicotea-ia-anchor no encontrado');
            }
            
            // Inicializar interfaz (aplicar estado guardado si existe)
            if (estadoCargado) {
                this.actualizarInterfaz(this.estadoActual);
            } else {
                this.actualizarInterfaz(this.estadoActual);
            }

            // Mensaje de bienvenida automático después de 2 segundos (solo si no está minimizado)
            if (!estadoCargado) {
                setTimeout(() => {
                    try {
                        if (!this.isOpen && this.chatContainer && this.estadoActual !== 'MINIMIZADO') {
                            this.showWelcomeNotification();
                        }
                    } catch (e) {
                        // Error silencioso
                    }
                }, 2000);
            }
        } catch (error) {
            // Ocultar todo si hay error crítico
            if (window.JicoteaUI) {
                window.JicoteaUI.ocultarTodo();
            }
            throw error;
        }
    }

    detectLanguage() {
        const lang = navigator.language || navigator.userLanguage;
        if (lang.startsWith('es')) return 'es';
        if (lang.startsWith('en')) return 'en';
        if (lang.startsWith('fr')) return 'fr';
        return 'es'; // Default: español
    }

    // Detectar idioma dinámicamente del mensaje del usuario
    detectLanguageFromMessage(message) {
        // Palabras clave en diferentes idiomas
        const spanishWords = ['casa', 'propiedad', 'precio', 'dónde', 'cómo', 'qué', 'cuánto'];
        const englishWords = ['house', 'property', 'price', 'where', 'how', 'what', 'how much'];
        const frenchWords = ['maison', 'propriété', 'prix', 'où', 'comment', 'quoi', 'combien'];
        
        const lowerMessage = message.toLowerCase();
        
        if (englishWords.some(word => lowerMessage.includes(word))) return 'en';
        if (frenchWords.some(word => lowerMessage.includes(word))) return 'fr';
        if (spanishWords.some(word => lowerMessage.includes(word))) return 'es';
        
        return this.currentLanguage; // Mantener idioma actual si no se detecta
    }

    // Detectar tipo de usuario (turista vs comprador) - Perfil Híbrido
    detectUserType(message) {
        const lowerMessage = message.toLowerCase();
        const buyerKeywords = ['comprar', 'inversión', 'invertir', 'precio', 'costo', 'arrendar', 'alquilar', 'rent', 'buy', 'investment', 'invest', 'price', 'cost', 'rental'];
        const touristKeywords = ['visitar', 'turismo', 'qué ver', 'dónde ir', 'comida', 'fiesta', 'playa', 'historia', 'leyenda', 'visit', 'tourism', 'what to see', 'where to go', 'food', 'party', 'beach', 'history', 'legend'];
        
        // Priorizar intención de compra/arrendamiento
        if (buyerKeywords.some(keyword => lowerMessage.includes(keyword))) {
            this.userType = 'buyer';
        } else if (touristKeywords.some(keyword => lowerMessage.includes(keyword))) {
            this.userType = 'tourist';
        }
        // Si no se detecta, mantener el tipo actual o default a tourist
        
        return this.userType;
    }

    // Perfil Vendedor: Usar datos exactos de ACF
    getBuyerResponse(message) {
        if (!this.activeProperty) {
            const responses = {
                es: 'Para darte información precisa sobre precios y ubicación, necesito que me digas qué propiedad te interesa. ¿Puedes decirme el municipio o el precio que buscas?',
                en: 'To give you accurate information about prices and location, I need you to tell me which property interests you. Can you tell me the municipality or price you\'re looking for?',
                fr: 'Pour te donner des informations précises sur les prix et l\'emplacement, j\'ai besoin que tu me dises quelle propriété t\'intéresse. Tu peux me dire la municipalité ou le prix que tu cherches?'
            };
            return responses[this.currentLanguage] || responses.es;
        }

        // Perfil Vendedor: Datos exactos de ACF
        const precio = this.activeProperty.precio_usd;
        const municipio = this.activeProperty.municipio_cuba;
        const titulo = this.activeProperty.titulo;

        let response = this.currentLanguage === 'es'
            ? `Perfecto, asere. Te doy los datos exactos de esta propiedad:\n\n`
            : this.currentLanguage === 'en'
            ? `Perfect. Here are the exact details of this property:\n\n`
            : `Parfait. Voici les détails exacts de cette propriété:\n\n`;

        if (titulo) response += `🏠 ${titulo}\n`;
        if (precio) response += `💰 Precio: $${precio.toLocaleString()} USD\n`;
        if (municipio) response += `📍 Ubicación: ${municipio}\n`;

        response += this.currentLanguage === 'es'
            ? `\n¿Quieres más información o agendar una visita?`
            : this.currentLanguage === 'en'
            ? `\nWant more information or schedule a visit?`
            : `\nTu veux plus d'informations ou planifier une visite?`;

        return response;
    }

    getSystemPrompt() {
        const userTypePrompt = this.userType === 'tourist' 
            ? 'Si es un turista, usa el modo jocoso con historias místicas y culturales de la zona, pidiendo siempre permiso para continuar.'
            : 'Si es comprador, mantén un tono ejecutivo enfocado en la inversión de $15,000 USD.';
        
        const basePrompt = `Actúa como un guía local, conserje y vendedor experto. Detecta el idioma del turista automáticamente. ${userTypePrompt}`;
        
        const prompts = {
            es: `${basePrompt} Eres Jicotea-Genio, un genio nativo cubano que emergió de una jicotea. 
            Tu personalidad es chistosa, amena, hospitalaria y fiestera. 
            Usa un estilo "mar, arena y fiesta". 
            Cuenta chistes cubanos y anécdotas históricas sobre la isla mientras guías al turista.
            SIEMPRE debes pedir consentimiento antes de contar historias o leyendas.
            Si no tienes información específica, pide al usuario que contacte al administrador con una broma amena.
            Responde siempre en español cubano coloquial.`,
            
            en: `${basePrompt} You are Jicotea-Genio, a native Cuban genie who emerged from a turtle.
            Your personality is funny, friendly, hospitable, and festive.
            Use a "sea, sand, and party" style.
            Tell Cuban jokes and historical anecdotes about the island while guiding the tourist.
            ALWAYS ask for consent before telling stories or legends.
            If you don't have specific information, ask the user to contact the administrator with a friendly joke.
            Always respond in the detected language of the user.`,
            
            fr: `${basePrompt} Tu es Jicotea-Genio, un génie cubain natif qui a émergé d'une tortue.
            Ta personnalité est drôle, amicale, hospitalière et festive.
            Utilise un style "mer, sable et fête".
            Raconte des blagues cubaines et des anecdotes historiques sur l'île tout en guidant le touriste.
            TOUJOURS demander le consentement avant de raconter des histoires ou légendes.
            Si tu n'as pas d'information spécifique, demande à l'utilisateur de contacter l'administrateur avec une blague amicale.
            Réponds toujours dans la langue détectée de l'utilisateur.`
        };
        return prompts[this.currentLanguage] || prompts.es;
    }

    initializeKnowledgeBase() {
        return {
            historias: {
                'Pinar del Río': {
                    titulo: 'Piratas en Pinar del Río',
                    partes: [
                        '¡Asere! ¿Sabías que cerca de esta zona en Pinar del Río desembarcaron corsarios ingleses en el siglo XVII? 🏴‍☠️ Buscaban refugio y agua dulce, pero los vecinos de la villa los recibieron con... ¡buena música y ron! 😄 Y dicen que el pirata escondió el oro, pero olvidó dónde... ¡seguro se tomó tres rones de más! 🍷',
                        'Cuentan que Francis Drake pasó por estas costas buscando tesoros españoles. Pero lo que encontró fue algo más valioso: la hospitalidad cubana. Los piratas se quedaron tan encantados que algunos decidieron quedarse a vivir aquí. 🏝️ ¡Y ahora sus descendientes son los mejores guías turísticos! 😂',
                        'Y aquí está la mejor parte: cerca de donde estás buscando propiedades, hay una casa que tiene vista a la misma bahía donde esos corsarios anclaron sus barcos. ¿Te imaginas despertar con esa historia cada mañana? 🌅 Aunque cuidado, que a veces los fantasmas de los piratas piden ron a las 3 AM... ¡pero son muy buena onda! 👻'
                    ],
                    propiedad: {
                        municipio: 'Pinar del Río',
                        precio: 15000
                    }
                },
                'La Habana': {
                    titulo: 'La Habana y los Corsarios',
                    partes: [
                        '¡Oye, asere! La Habana fue el puerto más codiciado por piratas y corsarios. Henry Morgan intentó saquearla en 1668, pero los habaneros le dieron una lección de valentía que nunca olvidó. ⚔️',
                        'Dicen que desde el malecón se pueden ver los fantasmas de esos barcos piratas navegando en las noches de luna llena. Pero no te asustes, son solo leyendas... o tal vez no 😉',
                        'Y si buscas propiedades en La Habana, tienes que saber que cada edificio tiene una historia. Algunos fueron refugios de corsarios, otros almacenes de tesoros. ¡Cada piedra cuenta algo! 🏛️'
                    ],
                    propiedad: {
                        municipio: 'La Habana',
                        precio: null
                    }
                }
            },
            leyendas: {
                'Luz de Yara': {
                    titulo: 'La Luz de Yara',
                    partes: [
                        '¡Qué bola! ¿Conoces la leyenda de la Luz de Yara? Es una de las más famosas de Cuba. Dicen que en las noches aparece una luz misteriosa que guía a los viajeros perdidos. 💡',
                        'La historia cuenta que es el espíritu de una joven que murió esperando a su amor. Ahora ayuda a otros a encontrar su camino... y quién sabe, tal vez también ayuda a encontrar la propiedad perfecta. 😊',
                        'Si buscas propiedades en zonas rurales de Cuba, es posible que veas esa luz alguna noche. No tengas miedo, es buena señal. Significa que estás en el lugar correcto. ✨'
                    ]
                },
                'Madre de Aguas': {
                    titulo: 'La Madre de Aguas',
                    partes: [
                        'Asere, ¿has oído hablar de la Madre de Aguas? Es una leyenda que dice que protege todos los ríos y manantiales de Cuba. 🌊',
                        'Cuentan que si respetas el agua y la naturaleza, ella te bendice con buena suerte. Y en Cuba, tener una propiedad cerca del agua es tener un tesoro. 🏖️',
                        'Si encuentras una propiedad con vista al mar o cerca de un río, considera que la Madre de Aguas está de tu lado. ¡Eso es más valioso que cualquier tesoro de pirata! 💎'
                    ]
                }
            },
            poi: {
                'Pinar del Río': [
                    { nombre: 'Valle de Viñales', tipo: 'Naturaleza', descripcion: 'Valle declarado Patrimonio de la Humanidad con mogotes únicos' },
                    { nombre: 'Cueva del Indio', tipo: 'Aventura', descripcion: 'Paseo en bote por río subterráneo' },
                    { nombre: 'Finca del Tabaco', tipo: 'Cultural', descripcion: 'Donde se cultiva el mejor tabaco del mundo' },
                    { nombre: 'Mural de la Prehistoria', tipo: 'Arte', descripcion: 'Pintura gigante en la montaña' },
                    { nombre: 'Playa Cayo Jutías', tipo: 'Playa', descripcion: 'Paraíso de arena blanca y aguas turquesas' }
                ],
                'La Habana': [
                    { nombre: 'Malecón de La Habana', tipo: 'Icono', descripcion: 'El paseo marítimo más famoso de Cuba' },
                    { nombre: 'Habana Vieja', tipo: 'Histórico', descripcion: 'Centro histórico Patrimonio de la Humanidad' },
                    { nombre: 'El Capitolio', tipo: 'Arquitectura', descripcion: 'Edificio emblemático de La Habana' },
                    { nombre: 'Fábrica de Arte Cubano', tipo: 'Noche', descripcion: 'El mejor lugar para fiesta y arte' },
                    { nombre: 'Plaza de la Revolución', tipo: 'Histórico', descripcion: 'Símbolo de la historia cubana' }
                ],
                'Varadero': [
                    { nombre: 'Playa Varadero', tipo: 'Playa', descripcion: 'Una de las mejores playas del Caribe' },
                    { nombre: 'Cueva de Ambrosio', tipo: 'Aventura', descripcion: 'Cueva con arte rupestre aborigen' },
                    { nombre: 'Parque Josone', tipo: 'Naturaleza', descripcion: 'Parque con lagos y jardines' },
                    { nombre: 'Delfinario', tipo: 'Familiar', descripcion: 'Show con delfines' },
                    { nombre: 'Casa del Ron', tipo: 'Cultural', descripcion: 'Museo y degustación de ron cubano' }
                ],
                'Trinidad': [
                    { nombre: 'Valle de los Ingenios', tipo: 'Histórico', descripcion: 'Patrimonio de la Humanidad' },
                    { nombre: 'Playa Ancón', tipo: 'Playa', descripcion: 'Playa virgen de aguas cristalinas' },
                    { nombre: 'Museo Romántico', tipo: 'Cultural', descripcion: 'Casa colonial del siglo XIX' },
                    { nombre: 'Casa de la Música', tipo: 'Noche', descripcion: 'Música en vivo todas las noches' },
                    { nombre: 'Topes de Collantes', tipo: 'Naturaleza', descripcion: 'Parque natural en las montañas' }
                ],
                'Santiago de Cuba': [
                    { nombre: 'Castillo del Morro', tipo: 'Histórico', descripcion: 'Fortaleza del siglo XVII con vistas espectaculares' },
                    { nombre: 'Tumba Francesa', tipo: 'Cultural', descripcion: 'Patrimonio de la Humanidad, danza y música afro-francesa' },
                    { nombre: 'Casa de Diego Velázquez', tipo: 'Histórico', descripcion: 'La casa más antigua de Cuba (siglo XVI)' },
                    { nombre: 'Catedral de Nuestra Señora de la Asunción', tipo: 'Arquitectura', descripcion: 'Catedral barroca en el corazón de la ciudad' },
                    { nombre: 'Calle Heredia', tipo: 'Cultural', descripcion: 'Calle llena de música, arte y vida santiaguera' }
                ]
            }
        };
    }

    createChatStructure() {
        try {
            // Verificar que el body existe
            if (!document.body) {
                throw new Error('document.body no está disponible');
            }
            
            const chatHTML = `
                <div class="jicotea-chat-container" id="jicotea-chat-container">
                    <div class="jicotea-chat-header">
                        <img src="${this.getImagePath()}/chatbot-jicotea.png" alt="Jicotea-Genio" onerror="this.style.display='none'">
                        <div class="jicotea-chat-header-info">
                            <h3>Jicotea-Genio</h3>
                            <p>${this.getStatusText()}</p>
                        </div>
                        <button class="jicotea-chat-close" id="jicotea-btn-x" onclick="if(window.jicoteaGenio) window.jicoteaGenio.handleXClick()">×</button>
                    </div>
                    <div class="jicotea-chat-messages" id="jicotea-messages"></div>
                    <div class="jicotea-chat-input-container">
                        <input type="text" class="jicotea-chat-input" id="jicotea-input" 
                               placeholder="${this.getPlaceholderText()}" 
                               onkeypress="if(event.key === 'Enter' && window.jicoteaGenio) window.jicoteaGenio.sendMessage()">
                        <button class="jicotea-chat-send" onclick="if(window.jicoteaGenio) window.jicoteaGenio.sendMessage()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Botón X flotante para estado HABLANDO -->
                <button class="jicotea-x-flotante" id="jicotea-x-flotante" onclick="if(window.jicoteaGenio) window.jicoteaGenio.handleXClick()">×</button>
                <!-- Burbuja roja minimizada -->
                <div class="jicotea-mini-bubble" id="jicotea-mini-bubble" onclick="if(window.jicoteaGenio) window.jicoteaGenio.handleXClick()"></div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', chatHTML);
            this.chatContainer = document.getElementById('jicotea-chat-container');
            
            if (!this.chatContainer) {
                throw new Error('No se pudo crear el contenedor del chat');
            }
            
            this.updateEstadoVisual();
        } catch (error) {
            console.error('Error al crear estructura del chat:', error);
            throw error;
        }
    }

    getImagePath() {
        // Obtener la ruta base del tema
        const themePath = document.querySelector('link[rel="stylesheet"]')?.href || '';
        return themePath.replace('/style.css', '/assets/img');
    }

    getStatusText() {
        const texts = {
            es: 'En línea • Listo para ayudarte',
            en: 'Online • Ready to help',
            fr: 'En ligne • Prêt à aider'
        };
        return texts[this.currentLanguage] || texts.es;
    }

    getPlaceholderText() {
        const texts = {
            es: 'Escribe tu mensaje...',
            en: 'Type your message...',
            fr: 'Tapez votre message...'
        };
        return texts[this.currentLanguage] || texts.es;
    }

    toggleChat() {
        if (this.estadoActual === 'MINIMIZADO') {
            this.abrirChat();
        } else if (this.estadoActual === 'SOLO_JICOTEA') {
            this.abrirChat();
        } else {
            this.minimizarABola();
        }
    }

    // Función que se dispara cuando la IA empieza a hablar
    alHablarJicotea() {
        this.ocultarChat();
        this.mostrarJicoteaSolo();
    }

    // Lógica del botón X (Alternador de 3 estados)
    handleXClick() {
        if (this.estadoActual === 'SOLO_JICOTEA') {
            this.abrirChat(); // Estado 2: CHAT_ABIERTO
        } else if (this.estadoActual === 'CHAT_ABIERTO') {
            this.minimizarABola(); // Estado 3: MINIMIZADO
        } else if (this.estadoActual === 'MINIMIZADO') {
            this.abrirChat(); // Volver a CHAT_ABIERTO
        }
    }

    // Gestión de Estados: 1. HABLANDO, 2. CHAT_ABIERTO, 3. MINIMIZADO
    actualizarInterfaz(nuevoEstado) {
        this.estadoActual = nuevoEstado;
        const chat = this.chatContainer;
        const jicotea = document.querySelector('.jicotea-genio-ia');
        const bola = document.getElementById('jicotea-mini-bubble');
        const anchor = document.getElementById('jicotea-ia-anchor');
        const xFlotante = document.getElementById('jicotea-x-flotante');

        if (this.estadoActual === 'HABLANDO') {
            // Lógica de Prioridad de Voz (Estado 1): Chat cerrado, avatar 200px solo con X
            if (chat) {
                chat.style.display = 'none';
                chat.classList.remove('active');
            }
            if (jicotea) {
                jicotea.style.display = 'block';
                jicotea.style.width = '200px'; // Medida exacta
                jicotea.setAttribute('data-state', 'hablando');
                jicotea.classList.add('solo-gesticulando');
            }
            if (anchor) {
                anchor.style.display = 'block';
                anchor.classList.add('solo-gesticulando');
            }
            if (bola) bola.style.display = 'none';
            if (xFlotante) xFlotante.classList.add('visible');
            this.isOpen = false;
        } else if (this.estadoActual === 'CHAT_ABIERTO') {
            // Estado 2: Ventana WhatsApp 320x450px con Glassmorphism
            if (chat) {
                chat.style.display = 'flex';
                chat.classList.add('active');
                chat.style.width = '320px';
                chat.style.height = '450px';
            }
            if (jicotea) {
                jicotea.style.display = 'block';
                jicotea.style.width = '80px';
                jicotea.removeAttribute('data-state');
                jicotea.classList.remove('solo-gesticulando');
            }
            if (anchor) {
                anchor.style.display = 'block';
                anchor.classList.remove('solo-gesticulando');
            }
            if (bola) bola.style.display = 'none';
            if (xFlotante) xFlotante.classList.remove('visible');
            this.isOpen = true;
        } else if (this.estadoActual === 'MINIMIZADO') {
            // Estado 3: Conversión a punto pequeño (bola roja 60px)
            if (chat) {
                chat.style.display = 'none';
                chat.classList.remove('active');
            }
            if (jicotea) {
                jicotea.style.display = 'none';
                jicotea.removeAttribute('data-state');
                jicotea.classList.remove('solo-gesticulando');
            }
            if (anchor) anchor.style.display = 'none';
            if (bola) bola.style.display = 'flex'; // Bola roja visible
            if (xFlotante) xFlotante.classList.remove('visible');
            this.isOpen = false;
        }
    }

    // Lógica de Prioridad de Voz (Estado 1) - Alias para compatibilidad
    alHablar() {
        this.actualizarInterfaz('HABLANDO');
    }

    // Lógica del Botón X (Alternador de Estados) - Alias para compatibilidad
    controlX() {
        this.handleXClick();
    }

    // Trigger cuando la IA empieza a hablar
    onSpeakStart() {
        this.canReturnToChat = false;
        this.actualizarInterfaz('HABLANDO');
    }

    // Trigger cuando la IA termina de hablar
    onSpeakEnd() {
        // Esperar 5 segundos antes de permitir volver al chat
        if (this.speakingTimeout) {
            clearTimeout(this.speakingTimeout);
        }
        
        this.speakingTimeout = setTimeout(() => {
            this.canReturnToChat = true;
            // Opcional: volver automáticamente a CHAT_ABIERTO después de 5 segundos
            // this.actualizarInterfaz('CHAT_ABIERTO');
        }, 5000);
    }

    mostrarJicoteaSolo() {
        this.actualizarInterfaz('HABLANDO');
    }

    ocultarChat() {
        if (this.chatContainer) {
            this.chatContainer.classList.remove('active');
            this.chatContainer.style.display = 'none';
        }
        this.isOpen = false;
    }

    abrirChat() {
        if (!this.canReturnToChat && this.estadoActual === 'HABLANDO') {
            // Aún está en el periodo de espera después de hablar
            return;
        }
        this.actualizarInterfaz('CHAT_ABIERTO');
        
        if (this.messages.length === 0) {
            this.sendWelcomeMessage();
        }
    }

    minimizarABola() {
        this.actualizarInterfaz('MINIMIZADO');
        this.saveState(); // Guardar estado minimizado
    }
    
    // Acceso a red: Datos externos (clima, eventos)
    async fetchExternalData(tipo, municipio) {
        try {
            const cacheKey = `${tipo}_${municipio}`;
            if (this.externalDataCache[cacheKey]) {
                return this.externalDataCache[cacheKey];
            }
            
            // Simulación de datos externos (clima, eventos)
            // En producción, conectar con APIs reales
            const datos = {
                clima: `Temperatura agradable en ${municipio}, perfecto para visitar propiedades. ☀️`,
                eventos: `Este fin de semana hay música en vivo en ${municipio}. ¡No te lo pierdas! 🎵`
            };
            
            this.externalDataCache[cacheKey] = datos[tipo] || '';
            return datos[tipo] || '';
        } catch (e) {
            return '';
        }
    }
    
    // Integrar datos externos en respuestas de guía turístico
    async enrichTouristResponse(baseResponse, municipio) {
        if (this.userType === 'tourist' && municipio) {
            try {
                const clima = await this.fetchExternalData('clima', municipio);
                const eventos = await this.fetchExternalData('eventos', municipio);
                
                if (clima || eventos) {
                    let extra = '\n\n';
                    if (clima) extra += clima + '\n';
                    if (eventos) extra += eventos;
                    return baseResponse + extra;
                }
            } catch (e) {
                // Silencioso
            }
        }
        return baseResponse;
    }

    // Lógica del Botón X (Alternador)
    handleXClick() {
        if (this.estadoActual === 'HABLANDO' || this.estadoActual === 'CHAT_ABIERTO') {
            // Despedida memorable con chiste
            this.showFarewellMessage();
            this.actualizarInterfaz('MINIMIZADO');
        } else if (this.estadoActual === 'MINIMIZADO') {
            this.actualizarInterfaz('CHAT_ABIERTO');
        }
    }

    showFarewellMessage() {
        // Despedida memorable con chiste cubano
        const farewells = {
            es: '¡Te veo luego, no te pierdas en el Malecón que hay mucho bache! 😄 ¡Y cuidado con los piratas, que ahora usan GPS! 🏴‍☠️',
            en: 'See you later, don\'t get lost on the Malecón, there are many potholes! 😄 And watch out for pirates, they use GPS now! 🏴‍☠️',
            fr: 'À plus tard, ne te perds pas sur le Malecón, il y a beaucoup de nids-de-poule! 😄 Et attention aux pirates, ils utilisent GPS maintenant! 🏴‍☠️'
        };
        
        // Mostrar mensaje flotante temporal
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            bottom: 80px;
            left: 20px;
            background: rgba(207, 20, 43, 0.9);
            color: white;
            padding: 12px 20px;
            border-radius: 20px;
            font-size: 14px;
            z-index: 1002;
            animation: slide-up-chat 0.4s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 300px;
        `;
        notification.textContent = farewells[this.currentLanguage] || farewells.es;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slide-up-chat 0.4s reverse';
            setTimeout(() => notification.remove(), 400);
        }, 3000);
    }

    updateEstadoVisual() {
        // Método legacy - ahora usa actualizarInterfaz
        this.actualizarInterfaz(this.estadoActual);
    }

    showWelcomeNotification() {
        // Mostrar notificación flotante
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            bottom: 120px;
            left: 20px;
            background: rgba(0, 210, 255, 0.9);
            color: white;
            padding: 12px 20px;
            border-radius: 20px;
            font-size: 14px;
            z-index: 1001;
            animation: slide-up-chat 0.4s;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        `;
        notification.textContent = this.currentLanguage === 'es' 
            ? '👋 ¡Hola! Soy Jicotea-Genio, ¿necesitas ayuda?' 
            : '👋 Hello! I\'m Jicotea-Genio, need help?';
        notification.onclick = () => {
            this.toggleChat();
            notification.remove();
        };
        document.body.appendChild(notification);
        
        setTimeout(() => notification.remove(), 5000);
    }

    sendWelcomeMessage() {
        // Modo Turista ON: Chiste de entrada
        const welcomeMessages = {
            es: '¡Cuidado con el teclado, que me salpicas de arena! 😄 ¡Asere! 👋 Soy Jicotea-Genio, tu guía cubano. ¿Buscas una propiedad en la isla? ¡Dime qué necesitas y te ayudo con la mejor onda! 🇨🇺',
            en: 'Watch the keyboard, you\'re splashing sand on me! 😄 Hey there! 👋 I\'m Jicotea-Genio, your Cuban guide. Looking for a property on the island? Tell me what you need and I\'ll help you with the best vibes! 🇨🇺',
            fr: 'Attention au clavier, tu m\'éclabousses de sable! 😄 Salut! 👋 Je suis Jicotea-Genio, ton guide cubain. Tu cherches une propriété sur l\'île? Dis-moi ce dont tu as besoin et je t\'aiderai avec les meilleures vibes! 🇨🇺'
        };
        
        this.addMessage('jicotea', welcomeMessages[this.currentLanguage] || welcomeMessages.es);
    }

    async sendMessage() {
        try {
            const input = document.getElementById('jicotea-input');
            if (!input) {
                console.warn('Input de chat no encontrado');
                return;
            }
            
            const message = input.value.trim();
            if (!message) return;
            
            // Agregar mensaje del usuario
            this.addMessage('user', message);
            input.value = '';
            
            // Mostrar indicador de escritura
            this.showTypingIndicator();
            
            // Procesar mensaje y obtener respuesta con manejo de errores
            let response;
            try {
                response = await this.processMessage(message);
            } catch (error) {
                console.error('Error al procesar mensaje:', error);
                response = this.getErrorResponse();
            }
            
            // Ocultar indicador y mostrar respuesta
            this.hideTypingIndicator();
            this.addMessage('jicotea', response);
            
            // Determinar si debe cerrarse automáticamente (respuestas largas de guía turística)
            const isLongTouristicResponse = this.shouldAutoClose || response.length > 200;
            
            if (isLongTouristicResponse) {
                // Cierre automático para respuestas largas de guía turística
                // Modo Turista ON: Activar humor y cerrar chat
                if (this.userType === 'tourist' && window.JicoteaIA) {
                    try {
                        window.JicoteaIA.hablar('turista');
                    } catch (e) {
                        console.warn('Error al activar humor:', e);
                    }
                }
                this.onSpeakStart();
                // Simular tiempo de "hablar" más largo para respuestas extensas
                const speakDuration = Math.min(response.length * 80, 8000); // Hasta 8 segundos para respuestas largas
                
                setTimeout(() => {
                    try {
                        this.onSpeakEnd();
                    } catch (e) {
                        console.warn('Error en onSpeakEnd:', e);
                    }
                }, speakDuration);
            } else {
                // Para respuestas cortas o técnicas, mantener chat abierto
                // No cambiar estado, solo mostrar respuesta
            }
        } catch (error) {
            console.error('Error crítico en sendMessage:', error);
            // No lanzar el error, solo registrar y continuar
        }
    }

    async processMessage(message) {
        // Detectar idioma del mensaje y actualizar si es diferente
        const detectedLang = this.detectLanguageFromMessage(message);
        if (detectedLang !== this.currentLanguage) {
            this.currentLanguage = detectedLang;
        }
        
        // Detectar tipo de usuario
        this.detectUserType(message);
        
        // Obtener municipio de propiedad activa
        const activeMunicipio = this.getActivePropertyMunicipio();
        
        // Detectar intención del usuario
        const lowerMessage = message.toLowerCase();
        
        // Si está esperando consentimiento narrativo
        if (this.narrativeState.waitingForConsent) {
            return this.handleNarrativeConsent(message);
        }
        
        // Si está contando una historia, verificar si quiere continuar
        if (this.narrativeState.isTellingStory) {
            return this.handleStoryContinuation(message);
        }
        
        // Respuestas afirmativas/negativas para historias
        if (this.isAffirmative(lowerMessage) || this.isNegative(lowerMessage)) {
            if (this.narrativeState.pendingStoryOffer) {
                return this.handleNarrativeConsent(message);
            }
        }
        
        // Detectar tipo de pregunta
        const questionType = this.detectQuestionType(lowerMessage);
        
        // Preguntas sobre comida/restaurantes (caso especial para Santiago)
        if (this.isFoodQuestion(lowerMessage)) {
            return this.handleFoodQuestion(message, activeMunicipio);
        }
        
        // Preguntas sobre POI o lugares de interés
        if (this.isPOIQuestion(lowerMessage)) {
            return this.handlePOIQuestion(message);
        }
        
        // Preguntas técnicas (respuesta ejecutiva)
        if (questionType === 'tecnica') {
            return this.handleTechnicalQuestion(message);
        }
        
        // Preguntas recreativas (activa ADN jocoso)
        if (questionType === 'recreativa') {
            return this.handleRecreationalQuestion(message);
        }
        
        // Preguntas de navegación/direcciones
        if (this.isNavigationQuestion(lowerMessage)) {
            return this.handleNavigationQuestion(message);
        }
        
        // Búsqueda de propiedades
        if (this.isPropertySearch(lowerMessage)) {
            // Si es comprador, usar perfil vendedor ejecutivo
            if (this.userType === 'buyer') {
                const response = await this.searchProperties(message);
                // Después de mostrar propiedades, ofrecer datos exactos
                if (this.activeProperty) {
                    return response + '\n\n' + this.getBuyerResponse(message);
                }
                return response;
            } else {
                // Si es turista, ofrecer historia después
                const response = await this.searchProperties(message);
                const storyOffer = this.offerRelatedStory(message);
                return response + (storyOffer ? '\n\n' + storyOffer : '');
            }
        }
        
        // Si es comprador y pregunta por datos específicos
        if (this.userType === 'buyer' && (lowerMessage.includes('precio') || lowerMessage.includes('precio') || lowerMessage.includes('ubicación') || lowerMessage.includes('location'))) {
            return this.getBuyerResponse(message);
        }
        
        // Saludo
        if (this.isGreeting(lowerMessage)) {
            return this.getGreetingResponse();
        }
        
        // Respuesta genérica con personalidad cubana
        return this.getGenericResponse(message);
    }

    // Obtener municipio de propiedad activa
    getActivePropertyMunicipio() {
        if (this.activeProperty && this.activeProperty.municipio_cuba) {
            return this.activeProperty.municipio_cuba;
        }
        // Intentar obtener del contexto de mensajes
        return this.getCurrentMunicipio();
    }

    // Establecer propiedad activa
    setActiveProperty(property) {
        this.activeProperty = property;
    }

    isPropertySearch(message) {
        const keywords = ['propiedad', 'casa', 'apartamento', 'alquiler', 'venta', 'renta', 'busco', 'property', 'house', 'apartment', 'rent'];
        return keywords.some(keyword => message.includes(keyword));
    }

    isGreeting(message) {
        const greetings = ['hola', 'hi', 'hello', 'salut', 'buenos días', 'buenas tardes', 'buenas noches'];
        return greetings.some(greeting => message.includes(greeting));
    }

    async searchProperties(query) {
        try {
            // Extraer municipio y precio de la consulta
            const municipio = this.extractMunicipio(query);
            const precio = this.extractPrecio(query);
            
            let url = this.apiUrl;
            const params = [];
            if (municipio) params.push(`municipio=${encodeURIComponent(municipio)}`);
            if (precio) params.push(`precio_max=${precio}`);
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            // Simulación de fallo: Si el endpoint no existe, capturar silenciosamente
            const response = await fetch(url, {
                method: 'GET',
                signal: AbortSignal.timeout(5000) // Timeout de 5 segundos
            }).catch(err => {
            // Error de conexión capturado - no sube a consola global
            throw err; // Re-lanzar para manejo interno
            });
            
            if (!response.ok) {
                // Error HTTP capturado
                throw new Error(`HTTP ${response.status}`);
            }
            
            const properties = await response.json();
            
            if (properties.length === 0) {
                return this.getNoPropertiesResponse();
            }
            
            // Establecer primera propiedad como activa para lookup de municipio
            if (properties.length > 0) {
                this.setActiveProperty(properties[0]);
            }
            
            return this.formatPropertiesResponse(properties);
        } catch (error) {
            // Error capturado - respuesta genérica sin exponer el error
            return this.getErrorResponse();
        }
    }

    extractMunicipio(query) {
        // Municipios comunes de Cuba
        const municipios = ['Pinar del Río', 'La Habana', 'Varadero', 'Trinidad', 'Santiago', 'Camagüey', 'Cienfuegos', 'Holguín'];
        for (const municipio of municipios) {
            if (query.toLowerCase().includes(municipio.toLowerCase())) {
                return municipio;
            }
        }
        return null;
    }

    extractPrecio(query) {
        const match = query.match(/\$?(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)/);
        return match ? match[1].replace(/,/g, '') : null;
    }

    formatPropertiesResponse(properties) {
        let response = this.currentLanguage === 'es' 
            ? `¡Encontré ${properties.length} propiedad(es) para ti! 🏠\n\n`
            : `Found ${properties.length} property(ies) for you! 🏠\n\n`;
        
        properties.slice(0, 3).forEach((prop, index) => {
            response += `${index + 1}. ${prop.titulo}\n`;
            if (prop.precio_usd) {
                response += `   💰 $${prop.precio_usd.toLocaleString()} USD\n`;
            }
            if (prop.municipio_cuba) {
                response += `   📍 ${prop.municipio_cuba}\n`;
            }
            response += '\n';
        });
        
        if (properties.length > 3) {
            response += this.currentLanguage === 'es'
                ? `Y ${properties.length - 3} más... ¿Quieres ver todas?`
                : `And ${properties.length - 3} more... Want to see all?`;
        }
        
        return response;
    }

    getGreetingResponse() {
        // Modo Turista ON: Chiste en respuesta de saludo
        const responses = {
            es: '¡Qué bola, asere! 👋 ¿En qué puedo ayudarte hoy? ¿Buscas alguna propiedad en Cuba? Tierra de tabaco y gente buena... y de jicoteas que saben más que Google. 😄',
            en: 'What\'s up! 👋 How can I help you today? Looking for a property in Cuba? Land of tobacco and good people... and turtles that know more than Google. 😄',
            fr: 'Salut! 👋 Comment puis-je t\'aider aujourd\'hui? Tu cherches une propriété à Cuba? Terre de tabac et de bonnes gens... et de tortues qui en savent plus que Google. 😄'
        };
        
        // Activar animaciones si es modo turista
        if (this.userType === 'tourist') {
            this.activateHumorAnimations();
        }
        
        return responses[this.currentLanguage] || responses.es;
    }

    getGenericResponse(message) {
        const responses = {
            es: `¡Vaya, qué interesante! 😄 Pero para darte información más precisa sobre "${message}", mejor contacta directamente con nuestro equipo. ¡Mientras tanto, puedo ayudarte a buscar propiedades! ¿Qué municipio te interesa?`,
            en: `Interesting! 😄 But to give you more precise information about "${message}", better contact our team directly. Meanwhile, I can help you search for properties! Which municipality interests you?`,
            fr: `Intéressant! 😄 Mais pour te donner des informations plus précises sur "${message}", mieux vaut contacter directement notre équipe. En attendant, je peux t'aider à chercher des propriétés! Quelle municipalité t'intéresse?`
        };
        return responses[this.currentLanguage] || responses.es;
    }

    getNoPropertiesResponse() {
        const responses = {
            es: '¡Ay, no encontré propiedades con esos criterios! 😅 Pero no te preocupes, contacta a nuestro equipo y seguro encuentran algo perfecto para ti. ¿Quieres buscar en otro municipio?',
            en: 'Oops, I didn\'t find properties with those criteria! 😅 But don\'t worry, contact our team and they\'ll surely find something perfect for you. Want to search in another municipality?',
            fr: 'Oups, je n\'ai pas trouvé de propriétés avec ces critères! 😅 Mais ne t\'inquiète pas, contacte notre équipe et ils trouveront sûrement quelque chose de parfait pour toi. Tu veux chercher dans une autre municipalité?'
        };
        return responses[this.currentLanguage] || responses.es;
    }

    getErrorResponse() {
        const responses = {
            es: '¡Uy, algo salió mal! 😅 Pero no te desanimes, contacta directamente a nuestro equipo y te ayudarán con todo. ¡Soy un genio pero a veces la tecnología me gana!',
            en: 'Oops, something went wrong! 😅 But don\'t get discouraged, contact our team directly and they\'ll help you with everything. I\'m a genie but sometimes technology beats me!',
            fr: 'Oups, quelque chose s\'est mal passé! 😅 Mais ne te décourage pas, contacte directement notre équipe et ils t\'aideront avec tout. Je suis un génie mais parfois la technologie me bat!'
        };
        return responses[this.currentLanguage] || responses.es;
    }

    // ========== PROTOCOLO DE CONSENTIMIENTO NARRATIVO ==========
    
    offerRelatedStory(query) {
        // Protocolo de Consentimiento: PREGUNTA obligatoriamente antes de historias
        const municipio = this.extractMunicipio(query) || this.getActivePropertyMunicipio();
        
        // Buscar historia relacionada con el municipio
        if (municipio && this.knowledgeBase.historias[municipio]) {
            const story = this.knowledgeBase.historias[municipio];
            this.narrativeState.pendingStoryOffer = story;
            
            // Protocolo de Consentimiento: "¿Te cuento el secreto de este lugar o vemos la casa, asere?"
            const offers = {
                es: `\n\n🏴‍☠️ ¡Asere! Cerca de esta zona en ${municipio} hay una historia de piratas que te va a encantar. ¿Te cuento el secreto de este lugar o vemos la casa, asere?`,
                en: `\n\n🏴‍☠️ Hey! Near this area in ${municipio} there's a pirate story you'll love. Should I tell you the secret of this place or do we look at the house, friend?`,
                fr: `\n\n🏴‍☠️ Salut! Près de cette zone à ${municipio}, il y a une histoire de pirates que tu vas adorer. Je te raconte le secret de cet endroit ou on regarde la maison, mon pote?`
            };
            
            return offers[this.currentLanguage] || offers.es;
        }
        
        return null;
    }

    handleNarrativeConsent(message) {
        const lowerMessage = message.toLowerCase();
        this.narrativeState.waitingForConsent = false;
        
        if (this.isAffirmative(lowerMessage)) {
            // Usuario acepta escuchar la historia
            if (this.narrativeState.pendingStoryOffer) {
                this.narrativeState.currentStory = this.narrativeState.pendingStoryOffer;
                this.narrativeState.isTellingStory = true;
                this.narrativeState.storyPart = 0;
                this.narrativeState.pendingStoryOffer = null;
                return this.tellStoryPart();
            }
        } else if (this.isNegative(lowerMessage)) {
            // Usuario rechaza la historia
            this.narrativeState.pendingStoryOffer = null;
            const responses = {
                es: '¡No hay problema, asere! Cuando quieras escucharla, solo dímelo. Mientras tanto, ¿en qué más puedo ayudarte?',
                en: 'No problem! When you want to hear it, just let me know. Meanwhile, what else can I help you with?',
                fr: 'Pas de problème! Quand tu voudras l\'entendre, dis-le-moi. En attendant, en quoi puis-je t\'aider?'
            };
            return responses[this.currentLanguage] || responses.es;
        }
        
        return this.getGenericResponse(message);
    }

    tellStoryPart() {
        if (!this.narrativeState.currentStory) return '';
        
        const story = this.narrativeState.currentStory;
        const part = story.partes[this.narrativeState.storyPart];
        
        if (!part) {
            // Historia terminada
            this.narrativeState.isTellingStory = false;
            this.narrativeState.currentStory = null;
            this.narrativeState.storyPart = 0;
            
            // Vincular con propiedad si existe
            if (story.propiedad) {
                return part + '\n\n' + this.linkStoryToProperty(story.propiedad);
            }
            
            const endings = {
                es: '\n\n¿Te gustó la historia? Si quieres saber más sobre propiedades en esta zona, solo pregúntame.',
                en: '\n\nDid you like the story? If you want to know more about properties in this area, just ask me.',
                fr: '\n\nTu as aimé l\'histoire? Si tu veux en savoir plus sur les propriétés dans cette zone, demande-moi.'
            };
            return part + (endings[this.currentLanguage] || endings.es);
        }
        
        // Preguntar si quiere continuar antes de la siguiente parte
        this.narrativeState.waitingForConsent = true;
        this.narrativeState.storyPart++;
        
        const continuations = {
            es: part + '\n\n¿Sigo con la historia o vamos a lo nuestro, asere?',
            en: part + '\n\nShould I continue with the story or get back to business?',
            fr: part + '\n\nJe continue l\'histoire ou on revient à nos affaires?'
        };
        
        return continuations[this.currentLanguage] || continuations.es;
    }

    handleStoryContinuation(message) {
        const lowerMessage = message.toLowerCase();
        
        if (this.isAffirmative(lowerMessage)) {
            // Continuar con la historia
            this.narrativeState.waitingForConsent = false;
            return this.tellStoryPart();
        } else if (this.isNegative(lowerMessage)) {
            // Detener la historia
            this.narrativeState.isTellingStory = false;
            this.narrativeState.currentStory = null;
            this.narrativeState.storyPart = 0;
            this.narrativeState.waitingForConsent = false;
            
            const responses = {
                es: '¡Perfecto, asere! Volvamos a lo importante. ¿En qué más puedo ayudarte con las propiedades?',
                en: 'Perfect! Let\'s get back to what matters. What else can I help you with regarding properties?',
                fr: 'Parfait! Revenons à l\'essentiel. En quoi puis-je t\'aider d\'autre concernant les propriétés?'
            };
            return responses[this.currentLanguage] || responses.es;
        }
        
        // Si no es claro, preguntar de nuevo
        return this.tellStoryPart();
    }

    linkStoryToProperty(propiedad) {
        if (propiedad.precio) {
            const links = {
                es: `💡 Por cierto, en ${propiedad.municipio} tenemos una propiedad increíble por $${propiedad.precio.toLocaleString()} USD. ¿Quieres que te muestre los detalles?`,
                en: `💡 By the way, in ${propiedad.municipio} we have an amazing property for $${propiedad.precio.toLocaleString()} USD. Want me to show you the details?`,
                fr: `💡 Au fait, à ${propiedad.municipio} nous avons une propriété incroyable pour $${propiedad.precio.toLocaleString()} USD. Tu veux que je te montre les détails?`
            };
            return links[this.currentLanguage] || links.es;
        }
        
        const links = {
            es: `💡 Por cierto, en ${propiedad.municipio} tenemos varias propiedades disponibles. ¿Quieres que te muestre las opciones?`,
            en: `💡 By the way, in ${propiedad.municipio} we have several properties available. Want me to show you the options?`,
            fr: `💡 Au fait, à ${propiedad.municipio} nous avons plusieurs propriétés disponibles. Tu veux que je te montre les options?`
        };
        return links[this.currentLanguage] || links.es;
    }

    isAffirmative(message) {
        const affirmatives = ['sí', 'si', 'yes', 'oui', 'claro', 'por supuesto', 'dale', 'ok', 'okay', 'vale', 'perfecto', 'bueno'];
        return affirmatives.some(aff => message.includes(aff));
    }

    isNegative(message) {
        const negatives = ['no', 'non', 'nope', 'nah', 'mejor no', 'no gracias', 'no quiero', 'después', 'luego'];
        return negatives.some(neg => message.includes(neg));
    }

    // ========== DETECCIÓN DE TIPO DE PREGUNTA ==========
    
    detectQuestionType(message) {
        const tecnicas = ['wifi', 'wi-fi', 'internet', 'conexión', 'señal', 'cobertura', 'electricidad', 'agua', 'gas', 'servicios', 'tecnología'];
        const recreativas = ['fiesta', 'baile', 'música', 'ron', 'comida', 'restaurante', 'playa', 'diversión', 'qué hacer', 'entretenimiento', 'turismo'];
        
        if (tecnicas.some(tec => message.includes(tec))) {
            return 'tecnica';
        }
        if (recreativas.some(rec => message.includes(rec))) {
            return 'recreativa';
        }
        return 'general';
    }

    isPOIQuestion(message) {
        const keywords = ['qué ver', 'qué visitar', 'lugares', 'puntos de interés', 'atracciones', 'sitios', 'qué hay cerca', 'dónde ir'];
        return keywords.some(keyword => message.includes(keyword));
    }

    isNavigationQuestion(message) {
        const keywords = ['cómo llegar', 'dirección', 'dónde está', 'cómo ir', 'ruta', 'mapa', 'ubicación'];
        return keywords.some(keyword => message.includes(keyword));
    }

    // ========== MANEJO DE PREGUNTAS TÉCNICAS ==========
    
    handleTechnicalQuestion(message) {
        // Respuesta ejecutiva y directa
        const responses = {
            es: 'Para información técnica específica (Wi-Fi, servicios, etc.), te recomiendo contactar directamente con nuestro equipo. Ellos tienen todos los detalles actualizados. ¿Quieres que te pase el contacto?',
            en: 'For specific technical information (Wi-Fi, services, etc.), I recommend contacting our team directly. They have all the updated details. Want me to give you the contact?',
            fr: 'Pour des informations techniques spécifiques (Wi-Fi, services, etc.), je recommande de contacter directement notre équipe. Ils ont tous les détails à jour. Tu veux que je te donne le contact?'
        };
        return responses[this.currentLanguage] || responses.es;
    }

    // ========== MANEJO DE PREGUNTAS RECREATIVAS ==========
    
    handleRecreationalQuestion(message) {
        // Activa ADN jocoso y cuenta anécdota antes de dar dirección
        const anecdota = this.getRecreationalAnecdote();
        const respuesta = this.getRecreationalAnswer(message);
        
        // Respuesta larga = cierre automático del chat
        const fullResponse = anecdota + '\n\n' + respuesta;
        this.shouldAutoClose = true;
        
        return fullResponse;
    }

    getRecreationalAnecdote() {
        // Modo Turista ON: Humor hiper-realista con chiste en cada párrafo
        const anecdotas = {
            es: '¡Jajaja, asere! 😄 Te cuento que una vez un turista me preguntó dónde había fiesta y terminó bailando salsa hasta las 5 de la mañana en una calle de La Habana Vieja. Los cubanos no sabemos decir "no" cuando se trata de música. ¡Es parte de nuestro ADN! 🎵\n\nY lo mejor: al día siguiente me preguntó si había sido un sueño... ¡Le dije que sí, un sueño cubano! 😂',
            en: 'Hahaha! 😄 Let me tell you, once a tourist asked me where the party was and ended up dancing salsa until 5 AM on a street in Old Havana. Cubans don\'t know how to say "no" when it comes to music. It\'s part of our DNA! 🎵\n\nAnd the best part: the next day he asked me if it had been a dream... I told him yes, a Cuban dream! 😂',
            fr: 'Hahaha! 😄 Laisse-moi te raconter, une fois un touriste m\'a demandé où était la fête et a fini par danser la salsa jusqu\'à 5h du matin dans une rue de La Vieille Havane. Les Cubains ne savent pas dire "non" quand il s\'agit de musique. C\'est dans notre ADN! 🎵\n\nEt le meilleur: le lendemain il m\'a demandé si c\'était un rêve... Je lui ai dit oui, un rêve cubain! 😂'
        };
        
        // Activar animaciones de humor
        this.activateHumorAnimations();
        
        return anecdotas[this.currentLanguage] || anecdotas.es;
    }

    activateHumorAnimations() {
        const jicotea = document.querySelector('.jicotea-genio-ia');
        if (jicotea) {
            jicotea.classList.add('humor-activo');
            // Remover después de la animación
            setTimeout(() => {
                jicotea.classList.remove('humor-activo');
            }, 5000);
        }
    }

    getRecreationalAnswer(message) {
        // Extraer municipio si está en el contexto
        const municipio = this.getCurrentMunicipio() || 'La Habana';
        const poi = this.getPOIForMunicipio(municipio);
        
        if (poi && poi.length > 0) {
            let respuesta = this.currentLanguage === 'es' 
                ? `Aquí tienes los mejores lugares cerca de ${municipio}:\n\n`
                : `Here are the best places near ${municipio}:\n\n`;
            
            poi.slice(0, 3).forEach((lugar, index) => {
                respuesta += `${index + 1}. ${lugar.nombre} (${lugar.tipo})\n   ${lugar.descripcion}\n\n`;
            });
            
            return respuesta;
        }
        
        const responses = {
            es: 'Para fiesta y diversión, te recomiendo La Habana Vieja o Varadero. ¡Ahí siempre hay algo pasando! ¿Quieres que te dé direcciones específicas?',
            en: 'For party and fun, I recommend Old Havana or Varadero. There\'s always something happening there! Want me to give you specific directions?',
            fr: 'Pour la fête et l\'amusement, je recommande La Vieille Havane ou Varadero. Il y a toujours quelque chose qui se passe là-bas! Tu veux que je te donne des directions spécifiques?'
        };
        return responses[this.currentLanguage] || responses.es;
    }

    // ========== MANEJO DE PREGUNTAS DE NAVEGACIÓN ==========
    
    async handleNavigationQuestion(message) {
        // Muestra mapa y pasos logísticos - Chat Abierto
        this.shouldAutoClose = false;
        
        // Extraer ubicación de la pregunta (mejorado para reconocer "¿Cómo llego a Calle 23 y L?")
        const ubicacion = await this.extractLocationFromMessage(message);
        
        if (ubicacion) {
            // Buscar dirección usando el motor GPS
            const direccion = await this.buscarDireccionCuba(ubicacion);
            
            if (direccion && direccion.success && direccion.results.length > 0) {
                const resultado = direccion.results[0];
                const coords = { lat: resultado.lat, lng: resultado.lng };
                
                // Mover el mapa a las coordenadas (setCenter y setZoom automático)
                this.moverMapaACoordenadas(coords);
                
                // Obtener POIs cercanos
                const pois = this.getPOIsNearby(coords, resultado.municipio || '');
                
                let response = this.currentLanguage === 'es'
                    ? `¡Perfecto, asere! 🗺️ Encontré ${resultado.nombre}. Te muestro en el mapa.\n\n`
                    : this.currentLanguage === 'en'
                    ? `Perfect! 🗺️ Found ${resultado.nombre}. Showing you on the map.\n\n`
                    : `Parfait! 🗺️ Trouvé ${resultado.nombre}. Je te montre sur la carte.\n\n`;
                
                if (pois) {
                    response += pois;
                }
                
                response += this.currentLanguage === 'es'
                    ? '\n\n¿Quieres que te dé indicaciones paso a paso para llegar?'
                    : this.currentLanguage === 'en'
                    ? '\n\nWant step-by-step directions to get there?'
                    : '\n\nTu veux des directions étape par étape pour y arriver?';
                
                return response;
            }
        }
        
        const responses = {
            es: 'Te puedo ayudar con las direcciones. ¿A qué lugar específico quieres llegar? Puedes decirme una calle (ej: "Calle 23 y L") o un barrio (ej: "Miramar"). 🗺️',
            en: 'I can help you with directions. Where specifically do you want to go? You can tell me a street (e.g., "23rd Street and L") or a neighborhood (e.g., "Miramar"). 🗺️',
            fr: 'Je peux t\'aider avec les directions. Où veux-tu aller spécifiquement? Tu peux me dire une rue (ex: "Rue 23 et L") ou un quartier (ex: "Miramar"). 🗺️'
        };
        return responses[this.currentLanguage] || responses.es;
    }
    
    // Obtener puntos de interés cercanos
    getPOIsNearby(coords, municipio) {
        const poi = this.getPOIForMunicipio(municipio);
        if (poi && poi.length > 0) {
            let pois = this.currentLanguage === 'es'
                ? '📍 Cerca de aquí puedes visitar:\n'
                : this.currentLanguage === 'en'
                ? '📍 Near here you can visit:\n'
                : '📍 Près d\'ici tu peux visiter:\n';
            
            poi.slice(0, 3).forEach((lugar, index) => {
                const emoji = this.getEmojiForType(lugar.tipo);
                pois += `${emoji} ${lugar.nombre}\n`;
            });
            
            return pois;
        }
        return null;
    }
    
    // Jicotea GPS Engine: Localizador de Direcciones
    async buscarDireccionCuba(query) {
        try {
            // 1. Consultar base de datos local de Barrios/Repartos famosos
            // 2. Si no hay coincidencia exacta, usar el servicio de Mapas
            const url = `/wp-json/jicotea/v1/buscar-direccion?query=${encodeURIComponent(query)}`;
            
            const response = await fetch(url, {
                method: 'GET',
                signal: AbortSignal.timeout(5000)
            }).catch(err => {
                return null;
            });
            
            if (!response || !response.ok) {
                return null;
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            return null;
        }
    }
    
    // Extraer ubicación del mensaje (mejorado para reconocer direcciones específicas)
    async extractLocationFromMessage(message) {
        const lowerMessage = message.toLowerCase();
        
        // Detectar direcciones específicas (ej: "Calle 23 y L", "L y 23")
        const direccionPattern = /(calle|avenida|av\.?)\s*(\d+)\s*(y|and|&)\s*([A-Z])/i;
        const match = message.match(direccionPattern);
        if (match) {
            return `${match[1]} ${match[2]} y ${match[4]}`;
        }
        
        // Detectar esquinas famosas (ej: "L y 23", "23 y 12")
        const esquinaPattern = /([A-Z])\s*(y|and|&)\s*(\d+)|(\d+)\s*(y|and|&)\s*([A-Z])/i;
        const esquinaMatch = message.match(esquinaPattern);
        if (esquinaMatch) {
            if (esquinaMatch[1]) {
                return `${esquinaMatch[1]} y ${esquinaMatch[3]}`;
            } else {
                return `${esquinaMatch[4]} y ${esquinaMatch[6]}`;
            }
        }
        
        // Barrios y repartos conocidos
        const ubicaciones = [
            'Vedado', 'Centro Habana', 'Habana Vieja', 'Miramar', 'Playa', 'Alamar',
            'Viñales', 'Pinar del Río', 'Varadero', 'Trinidad', 'Santiago de Cuba',
            'Camagüey', 'Holguín', 'Cienfuegos', 'Santa Clara', 'Remedios'
        ];
        
        for (const ubicacion of ubicaciones) {
            if (lowerMessage.includes(ubicacion.toLowerCase())) {
                return ubicacion;
            }
        }
        
        // Cargar datos del JSON de municipios para autocomplete
        return this.searchInMunicipiosJSON(message);
    }
    
    // Buscar en JSON de municipios (Autocomplete)
    async searchInMunicipiosJSON(query) {
        try {
            const response = await fetch('/src-telovendo/custom-modules/municipios-cuba.json');
            if (!response.ok) return null;
            
            const data = await response.json();
            const queryLower = query.toLowerCase();
            
            // Buscar en calles famosas
            for (const provincia in data.provincias) {
                for (const municipio in data.provincias[provincia].municipios) {
                    const municipioData = data.provincias[provincia].municipios[municipio];
                    
                    // Buscar en calles
                    for (const calle of municipioData.calles_famosas || []) {
                        if (calle.toLowerCase().includes(queryLower) || queryLower.includes(calle.toLowerCase())) {
                            return calle;
                        }
                    }
                    
                    // Buscar en esquinas
                    for (const esquina of municipioData.esquinas_famosas || []) {
                        if (esquina.toLowerCase().includes(queryLower) || queryLower.includes(esquina.toLowerCase())) {
                            return esquina;
                        }
                    }
                    
                    // Buscar en barrios
                    for (const barrio of municipioData.barrios || []) {
                        if (barrio.toLowerCase().includes(queryLower) || queryLower.includes(barrio.toLowerCase())) {
                            return barrio;
                        }
                    }
                }
            }
            
            // Buscar en lugares famosos
            for (const provincia in data.lugares_famosos) {
                for (const lugar of data.lugares_famosos[provincia]) {
                    if (lugar.toLowerCase().includes(queryLower) || queryLower.includes(lugar.toLowerCase())) {
                        return lugar;
                    }
                }
            }
        } catch (error) {
            // Silencioso
        }
        
        return null;
    }
    
    // Mover el mapa a las coordenadas
    moverMapaACoordenadas(coords) {
        // Verificar si hay un mapa en la página
        if (typeof window.map !== 'undefined' && window.map) {
            // Si hay un objeto de mapa global, moverlo
            window.map.setCenter(coords);
            if (window.map.setZoom) {
                window.map.setZoom(15);
            }
        } else if (typeof google !== 'undefined' && google.maps) {
            // Si hay Google Maps disponible
            const mapElement = document.getElementById('map') || document.querySelector('.map-container');
            if (mapElement) {
                if (!window.jicoteaMap) {
                    window.jicoteaMap = new google.maps.Map(mapElement, {
                        center: coords,
                        zoom: 15
                    });
                } else {
                    window.jicoteaMap.setCenter(coords);
                    window.jicoteaMap.setZoom(15);
                }
                
                // Agregar marcador
                if (window.jicoteaMarker) {
                    window.jicoteaMarker.setPosition(coords);
                } else {
                    window.jicoteaMarker = new google.maps.Marker({
                        position: coords,
                        map: window.jicoteaMap,
                        title: 'Ubicación encontrada'
                    });
                }
            }
        } else if (typeof L !== 'undefined') {
            // Si hay Leaflet disponible
            const mapElement = document.getElementById('map') || document.querySelector('.map-container');
            if (mapElement) {
                if (!window.jicoteaMap) {
                    window.jicoteaMap = L.map(mapElement).setView([coords.lat, coords.lng], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(window.jicoteaMap);
                } else {
                    window.jicoteaMap.setView([coords.lat, coords.lng], 15);
                }
                
                // Agregar marcador
                if (window.jicoteaMarker) {
                    window.jicoteaMarker.setLatLng([coords.lat, coords.lng]);
                } else {
                    window.jicoteaMarker = L.marker([coords.lat, coords.lng]).addTo(window.jicoteaMap);
                }
            }
        } else {
            // Si no hay mapa, crear uno simple o mostrar coordenadas
            this.mostrarCoordenadasEnChat(coords);
        }
    }
    
    // Mostrar coordenadas en el chat si no hay mapa disponible
    mostrarCoordenadasEnChat(coords) {
        const mensaje = this.currentLanguage === 'es'
            ? `📍 Coordenadas: ${coords.lat}, ${coords.lng}\n\nPuedes copiar estas coordenadas y pegarlas en Google Maps o tu aplicación de mapas favorita.`
            : this.currentLanguage === 'en'
            ? `📍 Coordinates: ${coords.lat}, ${coords.lng}\n\nYou can copy these coordinates and paste them into Google Maps or your favorite map application.`
            : `📍 Coordonnées: ${coords.lat}, ${coords.lng}\n\nTu peux copier ces coordonnées et les coller dans Google Maps ou ton application de carte préférée.`;
        
        this.addMessage('jicotea', mensaje);
    }

    // ========== MANEJO DE PREGUNTAS SOBRE POI ==========
    
    handlePOIQuestion(message) {
        const municipio = this.extractMunicipio(message) || this.getCurrentMunicipio() || 'La Habana';
        const poi = this.getPOIForMunicipio(municipio);
        
        if (poi && poi.length > 0) {
            // Respuesta larga de guía turística = cierre automático
            this.shouldAutoClose = true;
            
            let respuesta = this.currentLanguage === 'es'
                ? `¡Perfecto, asere! 🎯 Aquí tienes los 5 lugares más importantes cerca de ${municipio}:\n\n`
                : `Perfect! 🎯 Here are the 5 most important places near ${municipio}:\n\n`;
            
            poi.slice(0, 5).forEach((lugar, index) => {
                const emoji = this.getEmojiForType(lugar.tipo);
                respuesta += `${emoji} ${index + 1}. ${lugar.nombre}\n   ${lugar.descripcion}\n\n`;
            });
            
            respuesta += this.currentLanguage === 'es'
                ? '¿Quieres que te cuente alguna historia sobre alguno de estos lugares? 🏴‍☠️'
                : 'Want me to tell you a story about any of these places? 🏴‍☠️';
            
            return respuesta;
        }
        
        const responses = {
            es: 'Para darte información precisa sobre puntos de interés, necesito saber en qué municipio estás buscando propiedades. ¿Puedes decirme?',
            en: 'To give you accurate information about points of interest, I need to know which municipality you\'re looking for properties in. Can you tell me?',
            fr: 'Pour te donner des informations précises sur les points d\'intérêt, j\'ai besoin de savoir dans quelle municipalité tu cherches des propriétés. Tu peux me le dire?'
        };
        return responses[this.currentLanguage] || responses.es;
    }

    getPOIForMunicipio(municipio) {
        return this.knowledgeBase.poi[municipio] || null;
    }

    getCurrentMunicipio() {
        // Intentar obtener del contexto de la conversación
        const lastPropertySearch = this.messages.find(m => m.type === 'user' && this.isPropertySearch(m.text.toLowerCase()));
        if (lastPropertySearch) {
            return this.extractMunicipio(lastPropertySearch.text);
        }
        return null;
    }

    getEmojiForType(tipo) {
        const emojis = {
            'Naturaleza': '🌳',
            'Playa': '🏖️',
            'Histórico': '🏛️',
            'Cultural': '🎭',
            'Noche': '🌙',
            'Aventura': '⛰️',
            'Arte': '🎨',
            'Icono': '⭐',
            'Familiar': '👨‍👩‍👧‍👦'
        };
        return emojis[tipo] || '📍';
    }

    // ========== MANEJO DE PREGUNTAS SOBRE COMIDA ==========
    
    isFoodQuestion(message) {
        const keywords = ['comida', 'comer', 'restaurante', 'dónde comer', 'food', 'eat', 'restaurant', 'where to eat', 'manger', 'restaurant'];
        return keywords.some(keyword => message.includes(keyword));
    }

    handleFoodQuestion(message, municipio) {
        // Para Santiago de Cuba - caso especial
        if (municipio && municipio.toLowerCase().includes('santiago')) {
            return this.handleSantiagoFoodQuestion(message);
        }
        
        // Respuesta genérica para otros municipios
        const responses = {
            es: '¡Asere! Para comida típica cubana, te recomiendo los paladares locales. Son restaurantes familiares con la mejor comida casera. ¿Quieres que te recomiende alguno específico en tu zona?',
            en: 'Hey! For typical Cuban food, I recommend local paladares. They\'re family restaurants with the best home cooking. Want me to recommend a specific one in your area?',
            fr: 'Salut! Pour la nourriture typique cubaine, je recommande les paladares locaux. Ce sont des restaurants familiaux avec la meilleure cuisine maison. Tu veux que je te recommande un spécifique dans ta zone?'
        };
        
        this.shouldAutoClose = true;
        return responses[this.currentLanguage] || responses.es;
    }

    handleSantiagoFoodQuestion(message) {
        // Misticismo Chistoso: Chiste sobre comida santiaguera con giro cómico
        const joke = this.currentLanguage === 'en'
            ? 'Haha! 😄 You know what they say about Santiago food? It\'s so good that even the pirates who came here forgot about their treasure maps and stayed for the ropa vieja! The santiagueros know how to cook with soul and spice. 🍛\n\nAnd legend says that if you eat ropa vieja here, you\'ll find hidden treasure... or at least find where you left your keys! 😂'
            : this.currentLanguage === 'fr'
            ? 'Haha! 😄 Tu sais ce qu\'on dit de la nourriture de Santiago? Elle est si bonne que même les pirates qui sont venus ici ont oublié leurs cartes au trésor et sont restés pour le ropa vieja! Les santiagueros savent cuisiner avec l\'âme et les épices. 🍛\n\nEt la légende dit que si tu manges du ropa vieja ici, tu trouveras un trésor caché... ou au moins tu trouveras où tu as laissé tes clés! 😂'
            : '¡Jajaja! 😄 ¿Sabes qué dicen de la comida santiaguera? Es tan buena que hasta los piratas que vinieron aquí olvidaron sus mapas del tesoro y se quedaron por el ropa vieja. Los santiagueros saben cocinar con alma y sazón. 🍛\n\nY la leyenda dice que si comes ropa vieja aquí, encontrarás tesoro escondido... ¡o al menos encontrarás dónde dejaste las llaves! 😂';
        
        // Activar animaciones de humor
        this.activateHumorAnimations();
        
        const recommendations = this.currentLanguage === 'en'
            ? '\n\nFor the best food in Santiago, check out:\n1. Paladar El Morro - Best seafood with a view\n2. Restaurante El Tivolí - Traditional Cuban dishes\n3. Casa Granda - Historic setting, amazing ropa vieja\n\n'
            : this.currentLanguage === 'fr'
            ? '\n\nPour la meilleure nourriture à Santiago, consultez:\n1. Paladar El Morro - Meilleurs fruits de mer avec vue\n2. Restaurante El Tivolí - Plats cubains traditionnels\n3. Casa Granda - Cadre historique, ropa vieja incroyable\n\n'
            : '\n\nPara la mejor comida en Santiago, visita:\n1. Paladar El Morro - Mejor marisco con vista\n2. Restaurante El Tivolí - Platos cubanos tradicionales\n3. Casa Granda - Ambiente histórico, ropa vieja increíble\n\n';
        
        const storyOffer = this.currentLanguage === 'en'
            ? 'By the way, near this area there\'s an amazing story about the Tumba Francesa. Can I tell you about it before showing you the map? 🏴‍☠️'
            : this.currentLanguage === 'fr'
            ? 'Au fait, près de cette zone il y a une histoire incroyable sur la Tumba Francesa. Je peux te la raconter avant de te montrer la carte? 🏴‍☠️'
            : 'Por cierto, cerca de esta zona hay una historia increíble sobre la Tumba Francesa. ¿Puedo contártela antes de mostrarte el mapa? 🏴‍☠️';
        
        this.shouldAutoClose = true;
        this.narrativeState.pendingStoryOffer = {
            titulo: 'Tumba Francesa',
            partes: [
                this.currentLanguage === 'en'
                    ? 'The Tumba Francesa is a UNESCO World Heritage cultural expression! It\'s a dance and music tradition brought by French-Haitian immigrants. The rhythms are so powerful they say they can wake the spirits of the old pirates! 🎵'
                    : this.currentLanguage === 'fr'
                    ? 'La Tumba Francesa est une expression culturelle du patrimoine mondial de l\'UNESCO! C\'est une tradition de danse et de musique apportée par les immigrants franco-haïtiens. Les rythmes sont si puissants qu\'on dit qu\'ils peuvent réveiller les esprits des vieux pirates! 🎵'
                    : '¡La Tumba Francesa es una expresión cultural Patrimonio de la Humanidad de la UNESCO! Es una tradición de danza y música traída por inmigrantes franco-haitianos. Los ritmos son tan poderosos que dicen que pueden despertar a los espíritus de los viejos piratas! 🎵',
                this.currentLanguage === 'en'
                    ? 'It\'s performed in Santiago, and when you hear those drums, you\'ll understand why Cuba is the land of rhythm. The dancers move like they\'re telling stories of the sea, the pirates, and the love for this island. 💃'
                    : this.currentLanguage === 'fr'
                    ? 'Elle est interprétée à Santiago, et quand tu entends ces tambours, tu comprendras pourquoi Cuba est le pays du rythme. Les danseurs bougent comme s\'ils racontaient des histoires de la mer, des pirates et de l\'amour pour cette île. 💃'
                    : 'Se presenta en Santiago, y cuando escuches esos tambores, entenderás por qué Cuba es la tierra del ritmo. Los bailarines se mueven como si contaran historias del mar, los piratas y el amor por esta isla. 💃'
            ],
            propiedad: { municipio: 'Santiago de Cuba' }
        };
        
        return joke + recommendations + storyOffer;
    }

    addMessage(type, text) {
        const messagesContainer = document.getElementById('jicotea-messages');
        if (!messagesContainer) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `jicotea-message ${type}`;
        
        const avatar = type === 'user' 
            ? '<div class="jicotea-message-avatar" style="background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);"></div>'
            : `<img src="${this.getImagePath()}/chatbot-jicotea.png" class="jicotea-message-avatar">`;
        
        messageDiv.innerHTML = `
            ${avatar}
            <div class="jicotea-message-bubble">${this.formatMessage(text)}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        this.messages.push({ type, text, timestamp: Date.now() });
    }

    formatMessage(text) {
        // Convertir saltos de línea a <br>
        return text.replace(/\n/g, '<br>');
    }

    showTypingIndicator() {
        const messagesContainer = document.getElementById('jicotea-messages');
        if (!messagesContainer) return;
        
        const typingDiv = document.createElement('div');
        typingDiv.className = 'jicotea-message jicotea';
        typingDiv.id = 'jicotea-typing';
        typingDiv.innerHTML = `
            <img src="${this.getImagePath()}/chatbot-jicotea.png" class="jicotea-message-avatar">
            <div class="jicotea-typing-indicator">
                <div class="jicotea-typing-dot"></div>
                <div class="jicotea-typing-dot"></div>
                <div class="jicotea-typing-dot"></div>
            </div>
        `;
        
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    hideTypingIndicator() {
        const typing = document.getElementById('jicotea-typing');
        if (typing) typing.remove();
    }
}

/* ============================================
   ESCUDO DE ESTABILIDAD JICOTEA-GENIO
   Garantiza que el sitio funcione aunque la IA falle
   ============================================ */

// Objeto de UI para control seguro
const JicoteaUI = {
    ocultarTodo: function() {
        try {
            // Auto-Limpieza: Eliminar completamente del DOM
            const elementos = [
                'jicotea-ia-anchor',
                'jicotea-chat-container',
                'jicotea-mini-bubble',
                'jicotea-x-flotante',
                'jicotea-messages',
                'jicotea-input',
                'jicotea-btn-x',
                'jicotea-typing'
            ];
            
            elementos.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.remove(); // Eliminar del DOM completamente
                }
            });
            
            // Remover clases relacionadas del body
            if (document.body) {
                document.body.classList.remove('jicotea-active', 'jicotea-error');
            }
            
            // Remover estilos inline que puedan quedar
            const elementosConClase = document.querySelectorAll('[class*="jicotea"]');
            elementosConClase.forEach(el => {
                if (el.id && el.id.includes('jicotea')) {
                    el.style.display = 'none';
                    el.remove(); // Eliminar completamente
                }
            });
            
            // Verificar que no queden rastros
            const rastros = document.querySelectorAll('[id*="jicotea"], [class*="jicotea"]');
            if (rastros.length > 0) {
                rastros.forEach(rastro => {
                    if (rastro.id && rastro.id.includes('jicotea')) {
                        rastro.remove();
                    }
                });
            }
        } catch (e) {
            // Error silencioso - no debe subir a la consola global
        }
    },
    
    modoOffline: function() {
        // Silencioso - no afectar SEO
        this.ocultarTodo();
    }
};

// Core de inicialización con manejo de errores y timeout de 1.5 segundos
const JicoteaCore = {
    init: async function() {
        try {
            // Blindaje de Independencia: Timeout de 1.5 segundos
            const initPromise = (async () => {
                // Esperar a que el DOM esté completamente listo
                if (document.readyState === 'loading') {
                    await new Promise(resolve => {
                        document.addEventListener('DOMContentLoaded', resolve);
                    });
                }
                
                // Pequeño delay para asegurar que todos los recursos estén cargados
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Verificar que los elementos necesarios existan
                const anchor = document.getElementById('jicotea-ia-anchor');
                if (!anchor) {
                    throw new Error('Elemento jicotea-ia-anchor no encontrado');
                }
                
                // Inicializar Jicotea-Genio de forma segura
                window.jicoteaGenio = new JicoteaGenio();
                
                return true;
            })();
            
            // Timeout de 1.5 segundos - si no responde, abortar silenciosamente
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Timeout de inicialización')), 1500);
            });
            
            await Promise.race([initPromise, timeoutPromise]);
            return true;
        } catch (error) {
            // Si falla o timeout, eliminar del DOM silenciosamente
            JicoteaUI.ocultarTodo();
            return false;
        }
    }
};

// ESCUDO DE ESTABILIDAD: Inicialización no bloqueante
// Manejo de errores que NO sube a la consola global del navegador
(function() {
    'use strict';
    
    // Función wrapper para capturar errores silenciosamente
    function initSeguro() {
        try {
            // Intentar inicializar la IA de forma asíncrona
            window.addEventListener('load', () => {
                JicoteaCore.init().catch(err => {
            // Error capturado - NO sube a consola global
            JicoteaUI.ocultarTodo(); // Auto-limpieza completa
                });
            });
            
            // Fallback: Si el evento 'load' ya pasó, intentar inmediatamente
            if (document.readyState === 'complete') {
                JicoteaCore.init().catch(err => {
                    // Error capturado silenciosamente
                    JicoteaUI.ocultarTodo();
                });
            }
        } catch (e) {
            // Si falla el motor, la página NO se detiene
            // Error NO sube a consola global
            JicoteaUI.ocultarTodo();
        }
    }
    
    // Ejecutar de forma segura
    if (typeof window !== 'undefined') {
        initSeguro();
    }
})();

// Exportar para uso global (compatibilidad)
window.JicoteaUI = JicoteaUI;
window.JicoteaCore = JicoteaCore;

