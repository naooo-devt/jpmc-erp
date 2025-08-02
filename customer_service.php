<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finances - James Polymer ERP</title>
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .suggested-question {
            padding: 15px 20px;
            border-radius: 30px;
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            font-size: 0.9rem;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .suggested-question:hover {
            background: #c7d2fe;
            transform: scale(1.05);
        }
        mark {
            background-color: #adc9fdff;
            padding: 1px 3px;
            border-radius: 3px;
        }

    </style>
    <link rel="icon" href="logo.png">
</head>
<body>
    <!--SideBar MENU -->
    <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content" style="flex: 1; padding: 20px; overflow-y: auto;">
            <!-- Header -->
            <div class="header" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h1 style="margin: 0; color: #2563eb; font-size: 1.5rem;">Customer Service</h1>
                <p style="margin: 5px 0 0 0; color: #2563eb;  font-size: 1.0rem;">Chat with our bot assistant</p>
            </div>

            <!-- Chat Interface -->
            <div class="chat-container" style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">

                <!-- Chat Header -->
                <div class="chat-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user" style="font-size: 1.2rem;"></i>
                        <span style="font-weight: 500;">User</span>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="chat-tabs" style="display: flex; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                    <button class="tab-btn active" style="flex: 1; padding: 16px; border: none; background: white; color: #667eea; font-weight: 600; cursor: pointer; border-bottom: 3px solid #667eea;">Chat with us!</button>
                    <button class="tab-btn" style="flex: 1; padding: 16px; border: none; background: transparent; color: #6c757d; font-weight: 500; cursor: pointer;">Connect with us!</button>
                    <button class="tab-btn" style="flex: 1; padding: 16px; border: none; background: transparent; color: #6c757d; font-weight: 500; cursor: pointer;">FAQs</button>
                </div>

                <!-- Chat Tab -->
                <div id="chat-tab" style="display: block;">
                    <div class="bot-info" style="padding: 16px 20px; background: #f8f9fa; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="bot-avatar" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">P</div>
                            <span style="font-weight: 600; color: #495057;">PolyBot</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button id="clearHistoryBtn" disabled style="background: transparent; border: none; padding: 0; width: 0; height: 0; overflow: hidden; pointer-events: none; opacity: 0;"></button>
                            <i class="fas fa-info-circle" style="color: #6c757d; cursor: pointer; font-size: 1.1rem;"></i>
                        </div>
                    </div>

                    <div id="polybot-info-panel" style="
                        display: none;
                        position: fixed;
                        top: 31%;
                        left: 80.55555%;
                        transform: translate(-50%, -50%);
                        width: 400px;
                        max-width: 90%;
                        background: white;
                        border: 1px solid #dee2e6;
                        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                        padding: 20px;
                        border-radius: 10px;
                        z-index: 9999;
                    ">
                        <h3 style="margin-top: 0; color: #495057;">About PolyBot</h3>
                        <p style="font-size: 0.9rem; color: #6c757d;">
                            PolyBot is your virtual assistant from James Polymer Manufacturing Corp. It helps you navigate the system, get quick answers to FAQs, and assist with tasks like inventory, HR, and more.
                        </p>
                        <p style="font-size: 0.9rem; color: #6c757d;">
                            Just type a message or click a suggested question to get started!
                        </p>
                    </div>


                    <!-- Messages -->
                    <div class="chat-messages" id="chat-content" style="height: 400px; padding: 20px; overflow-y: auto; background: white;">
                        <div class="message bot-message" style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px;">
                            <div class="message-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem;">P</div>
                            <div class="message-bubble" style="background: #f8f9fa; padding: 12px 16px; border-radius: 18px; max-width: 70%; color: #495057;">Hello! I am Poly, your chat bot assistant from JPMC.</div>
                        </div>
                    </div>

                    <div id="suggested-questions" style="margin-top: 10px; overflow-x: auto; white-space: nowrap; padding-bottom: 8px; padding-left: 10px; padding-right: 10px;">
                        <div style="display: inline-flex; gap: 10px;">
                            <button class="suggested-question">What are your business hours?</button>
                            <button class="suggested-question">How can I place an order?</button>
                            <button class="suggested-question">Where is your company located?</button>
                            <button class="suggested-question">Do you offer bulk discounts?</button>
                            <button class="suggested-question">What are your delivery times?</button>
                            <button class="suggested-question">Can I request a product demo?</button>
                        </div>
                    </div>

                    <div class="message-input-container" style="padding: 20px; background: #f8f9fa; border-top: 1px solid #e9ecef;">
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <input type="text" class="message-input" placeholder="Enter a message" style="flex: 1; padding: 12px 16px; border: 2px solid #e9ecef; border-radius: 25px;">
                            <button class="send-btn" style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>

               <!-- Connect With Us Tab -->
                <div id="connect-tab" style="text-align: center; display: none; padding: 40px 20px;">
                    <h3 style="color: #495057; margin-bottom: 30px; font-size: 1.5rem;">Connect with us!</h3>
                    <div style="max-width: 400px; margin: 0 auto; text-align: center;">

                        <!-- Address -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 600;">
                                <i class="fas fa-map-marker-alt" style="margin-right: 8px; color: #dc3545;"></i>Address:
                            </label>
                            <p style="color: #6c757d; margin: 0;">16 Panapaan 2, Bacoor City, 4102, Cavite, Philippines</p>
                        </div>

                        <!-- Facebook -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 600;">
                                <i class="fab fa-facebook-square" style="margin-right: 8px; color: #4267B2;"></i>Facebook:
                            </label>
                            <a href="https://www.facebook.com/JamesPolymersMfg.International" target="_blank" style="color: #0d6efd; text-decoration: underline;">
                                https://www.facebook.com/JamesPolymersMfg.International
                            </a>
                        </div>

                        <!-- Website -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 600;">
                                <i class="fas fa-globe" style="margin-right: 8px; color: #17a2b8;"></i>Website:
                            </label>
                            <a href="https://www.james-polymers.com/" target="_blank" style="color: #0d6efd; text-decoration: underline;">
                                https://www.james-polymers.com/
                            </a>
                        </div>

                        <!-- Email -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 600;">
                                <i class="fas fa-envelope" style="margin-right: 8px; color: #f39c12;"></i>Email:
                            </label>
                            <a href="mailto:jamespro.asia101@gmail.com" style="color: #0d6efd; text-decoration: underline;">
                                jamespro.asia101@gmail.com
                            </a><br>
                            <a href="mailto:jamespro_asia@yahoo.com" style="color: #0d6efd; text-decoration: underline;">
                                jamespro_asia@yahoo.com
                            </a>
                        </div>

                        <!-- Contact Number -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 600;">
                                <i class="fas fa-phone" style="margin-right: 8px; color: #28a745;"></i>Contact Number:
                            </label>
                            <a href="tel:09399359753" style="color: #0d6efd; text-decoration: underline;">09399359753</a>
                        </div>
                    </div>
                </div>


                <!-- FAQs Tab -->
                <div id="faq-tab" style="display: none; padding: 40px 20px;">
                    <div style="max-width: 600px; margin: 0 auto 30px; text-align: left;">
                        <input type="text" id="faqSearchInput" placeholder="Search a question..." style="width: 100%; padding: 12px 16px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem;">
                    </div>
                    <h3 style="color: #495057; margin-bottom: 30px; font-size: 1.5rem; text-align: center;">Frequently Asked Questions</h3>
                    <div style="max-width: 600px; margin: 0 auto; text-align: left;">
                        <div class="faq-item" data-question="How can I place an order?" style="margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="color: #495057; margin-bottom: 8px;">How can I place an order?</h4>
                            <p style="color: #6c757d; margin: 0;">You can place an order through our website or contact our sales team directly.</p>
                        </div>
                        <div class="faq-item" data-question="What are your delivery times?" style="margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="color: #495057; margin-bottom: 8px;">What are your delivery times?</h4>
                            <p style="color: #6c757d; margin: 0;">Standard delivery takes 3–5 business days. Express delivery is available for urgent orders.</p>
                        </div>
                        <div class="faq-item" data-question="Do you offer bulk discounts?" style="margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="color: #495057; margin-bottom: 8px;">Do you offer bulk discounts?</h4>
                            <p style="color: #6c757d; margin: 0;">Yes, we offer special pricing for bulk orders. Contact our sales team for details.</p>
                        </div>
                        <div class="faq-item" data-question="What is your  business hours?" style="margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="color: #495057; margin-bottom: 8px;">What is your  business hours?</h4>
                            <p style="color: #6c757d; margin: 0;">Our business hours are Monday to Friday from 8:00 AM to 5:00 PM.</p>
                        </div>
                        <div class="faq-item" data-question="Where is your company located?" style="margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="color: #495057; margin-bottom: 8px;">Where is your company located?</h4>
                            <p style="color: #6c757d; margin: 0;">Our company is located in the Philippines at 16 Aguinaldo Hi-Way, Panapaan II, Bacoor, Cavite. Landmark: Urban Generation.</p>
                        </div>
                        <div class="faq-item" data-question="Can I request a product demo?" style="margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="color: #495057; margin-bottom: 8px;">Can I request a product demo?</h4>
                            <p style="color: #6c757d; margin: 0;">Please contact our sales team for details.</p>
                        </div>
                    </div>
                </div>
            </div>



    <script src="assets/js/script.js"></script>
    <script>
    document.getElementById('faqSearchInput').addEventListener('input', function () {
        const searchTerm = this.value.trim().toLowerCase();
        const faqContainer = document.querySelector('#faqs-content > div > div:last-child');
        const faqItems = Array.from(faqContainer.querySelectorAll('.faq-item'));

        const matched = [];
        const unmatched = [];

        faqItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                matched.push(item);
            } else {
                unmatched.push(item);
            }
        });

        // Clear container
        faqContainer.innerHTML = '';

        // Append matched first, then unmatched
        matched.forEach(item => faqContainer.appendChild(item));
        unmatched.forEach(item => faqContainer.appendChild(item));
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const chatContent = document.getElementById('chat-content');
        const connectContent = document.getElementById('connect-content');
        const faqsContent = document.getElementById('faqs-content');
        const messageInputContainer = document.querySelector('.message-input-container');
        const infoIcon = document.querySelector('.fa-info-circle');
        const infoPanel = document.getElementById('polybot-info-panel');

        // Toggle panel visibility when icon is clicked
        infoIcon.addEventListener('click', function (e) {
        e.stopPropagation(); // Prevent triggering the window click
        infoPanel.style.display = infoPanel.style.display === 'none' ? 'block' : 'none';
                });

        // Close panel when clicking outside
        document.addEventListener('click', function () {
        infoPanel.style.display = 'none';
                });

        // Prevent panel from closing when clicking inside it
        infoPanel.addEventListener('click', function (e) {
        e.stopPropagation();
                 });

        
        tabBtns.forEach((btn, index) => {
            btn.addEventListener('click', function() {
                // Remove active class from all tabs
                tabBtns.forEach(tab => {
                    tab.classList.remove('active');
                    tab.style.background = 'transparent';
                    tab.style.color = '#6c757d';
                    tab.style.borderBottom = 'none';
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                this.style.background = 'white';
                this.style.color = '#667eea';
                this.style.borderBottom = '3px solid #667eea';
                
                // Show appropriate content based on tab index
                if (index === 0) { // Chat with us!
                    chatContent.style.display = 'block';
                    connectContent.style.display = 'none';
                    faqsContent.style.display = 'none';
                    messageInputContainer.style.display = 'block';
                } else if (index === 1) { // Connect with us!
                    chatContent.style.display = 'none';
                    connectContent.style.display = 'block';
                    faqsContent.style.display = 'none';
                    messageInputContainer.style.display = 'none';
                } else if (index === 2) { // FAQs
                    chatContent.style.display = 'none';
                    connectContent.style.display = 'none';
                    faqsContent.style.display = 'block';
                    messageInputContainer.style.display = 'none';
                }
            });
        });

        // AI PolyBot Knowledge Base with Predefined Q&A
        const polyBotKnowledge = {
            // Greetings and Basic Responses
            greetings: {
                patterns: ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening'],
                responses: [
                    "Hi there! 👋 I'm Poly, your AI assistant from James Polymer Manufacturing Corporation. How can I help you today?",
                    "Hello! 😊 Welcome to JPMC. I'm Poly, ready to assist you with any questions about our products and services.",
                    "Hey! 👋 I'm Poly from James Polymer. How can I help you today?",
                    "Hi! 😄 Welcome! What would you like to know about our company?",
                    "Hello there! 👋 How are you doing today? I'm Poly, your AI assistant."
                ]
            },
            
            // Company Information
            company_info: {
                patterns: [ 'about', 'jpmc', 'what is', 'tell me about', 'who are you', 'what does', 'what do'],
                responses: [
                    "James Polymer Manufacturing Corporation (JPMC) is a polymer manufacturing and plastic products company established in the Philippines. Our mission is to provide high-quality polymer solutions and innovative plastic products. We aim to be a leading manufacturer in the polymer industry.",
                    "We are James Polymer Manufacturing Corporation, specializing in polymer manufacturing and plastic products. We're committed to providing high-quality solutions and innovative products to our customers.",
                    "JPMC is a Philippine-based company focused on polymer manufacturing. We provide high-quality polymer solutions and innovative plastic products for various industries."
                ]
            },
            
            // Products
            products: {
                patterns: ['product', 'material', 'abs', 'hips', 'pp', 'nylon', 'pvc', 'what do you make', 'materials'],
                responses: [
                    "We manufacture various polymer materials including ABS (Acrylonitrile Butadiene Styrene), HIPS (High Impact Polystyrene), PP (Polypropylene), PS (Polystyrene), Nylon, and PVC. Our products are used in automotive parts, electronics, packaging, construction materials, and consumer goods. All products meet international quality standards.",
                    "Our product line includes ABS, HIPS, PP, PS, Nylon, and PVC materials. These are used in automotive, electronics, packaging, construction, and consumer goods industries. We maintain high quality standards across all products.",
                    "We produce polymer materials like ABS, HIPS, PP, PS, Nylon, and PVC. These materials serve automotive, electronics, packaging, construction, and consumer goods sectors with international quality standards."
                ]
            },
            
            // Contact Information
            contact: {
                patterns: ['contact', 'phone', 'email', 'address', 'location', 'hours', 'reach', 'location', 'located'],
                responses: [
                    "You can contact us at jamespro.asia101@gmail.com or jamespro_asia@yahoo.com or call 09399359753. \nWe're located in the Philippines at 16 Aguinaldo Hi-Way, Panapaan II, Bacoor, Cavite. \nLandmark: Urban Generation. \nOur business hours are Monday to Friday, 8:00 AM - 5:00 PM.",
                ]
            },
            
            // Pricing
            pricing: {
                patterns: ['price', 'cost', 'how much', 'quote', 'pricing', 'rates'],
                responses: [
                    "Pricing varies depending on the material type, quantity, and specifications. For accurate pricing, please contact our sales team with your specific requirements.",
                    "Our pricing depends on material type, quantity, and specifications. Please contact our sales team for detailed quotes based on your needs.",
                    "Pricing is customized based on material type, quantity, and specifications. Contact our sales team for accurate quotes tailored to your requirements."
                ]
            },
            
            // Services
            services: {
                patterns: ['service', 'offer', 'custom', 'manufacturing', 'consulting', 'support'],
                responses: [
                    "We offer custom polymer manufacturing, material selection and technical support, local and international shipping, and 24/7 customer support and technical assistance.",
                    "Our services include custom polymer manufacturing, technical consulting, material selection support, shipping services, and round-the-clock customer support.",
                    "We provide custom manufacturing, technical consulting, material selection, shipping services, and 24/7 customer support for all your polymer needs."
                ]
            },
            
            // Delivery/Shipping
            delivery: {
                patterns: ['delivery', 'shipping', 'transport', 'when will', 'how long', 'shipping time'],
                responses: [
                    "We offer local and international shipping. Standard delivery takes 3-5 business days for local orders. Express delivery is available for urgent orders.",
                    "Local delivery takes 3-5 business days, with express options available. We also provide international shipping services.",
                    "Standard local delivery is 3-5 business days. We offer express shipping for urgent orders and international shipping services."
                ]
            },
            
            // Quality
            quality: {
                patterns: ['quality', 'standard', 'certification', 'warranty', 'guarantee'],
                responses: [
                    "All our products meet international quality standards. We provide standard warranty on all products and return policy for defective items.",
                    "We maintain international quality standards across all products. Standard warranty and return policies are available for customer protection.",
                    "Our products meet international quality standards with warranty coverage and return policies for customer satisfaction."
                ]
            },
            
            // Ordering
            order: {
                patterns: ['order', 'buy', 'purchase', 'place order', 'how to order', 'how do I order'],
                responses: [
                    "You can place an order through our website or contact our sales team directly. We offer flexible payment terms for bulk orders.",
                    "Orders can be placed via our website or by contacting our sales team. We provide flexible payment options for bulk orders.",
                    "Place orders through our website or contact our sales team. We offer flexible payment terms, especially for bulk orders."
                ]
            },
            
            // Farewells
            farewell: {
                patterns: ['bye', 'goodbye', 'see you', 'thank you', 'thanks', 'end'],
                responses: [
                    "You're very welcome! 😊 Have a great day, and feel free to return if you have more questions about James Polymer Manufacturing Corporation.",
                    "Thank you for chatting with me! 👋 Don't hesitate to reach out again for any polymer-related inquiries.",
                    "You're welcome! 😄 Have a wonderful day, and feel free to come back anytime for more information about JPMC.",
                    "Thanks for chatting! 👋 Feel free to return if you need any more assistance with our products or services.",
                    "You're very welcome! 😊 Have a great day ahead!"
                ]
            },
            
            // Default/Unknown
            default: {
                patterns: [],
                responses: [
                    "I'm not sure I understand. Could you please rephrase your question? I can help you with information about our company, products, services, contact details, pricing, and more.",
                    "I didn't quite catch that. Could you try asking in a different way? I can assist with company info, products, services, contact details, and pricing.",
                    "I'm not sure about that. Could you please rephrase? I'm here to help with information about James Polymer Manufacturing Corporation."
                ]
            }
        };

        // Simple Code-Based PolyBot
        class SimplePolyBot {
            constructor() {
                this.sessionId = this.generateSessionId();
                this.loadFromLocalStorage();
            }

            generateSessionId() {
                return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }

            loadFromLocalStorage() {
                try {
                    const history = localStorage.getItem('polybot_history');
                    this.conversationHistory = history ? JSON.parse(history) : [];
                    
                    const count = localStorage.getItem('polybot_interaction_count');
                    this.interactionCount = count ? parseInt(count) : 0;
                    
                    console.log('Loaded from localStorage:', {
                        historyLength: this.conversationHistory.length,
                        interactionCount: this.interactionCount
                    });
                } catch (error) {
                    console.error('Error loading from localStorage:', error);
                    this.conversationHistory = [];
                    this.interactionCount = 0;
                }
            }

            saveToLocalStorage() {
                try {
                    const recentHistory = this.conversationHistory.slice(-50);
                    localStorage.setItem('polybot_history', JSON.stringify(recentHistory));
                    localStorage.setItem('polybot_interaction_count', this.interactionCount.toString());
                    
                    console.log('Saved to localStorage:', {
                        historyLength: recentHistory.length,
                        interactionCount: this.interactionCount
                    });
                } catch (error) {
                    console.error('Error saving to localStorage:', error);
                }
            }

            addMessage(message, isUser = true, category = null) {
                this.conversationHistory.push({
                    message: message,
                    isUser: isUser,
                    timestamp: Date.now(),
                    category: category
                });
                this.interactionCount++;
                this.saveToLocalStorage();
                
                // Save analytics every 5 interactions
                if (this.interactionCount % 5 === 0) {
                    this.saveAnalytics();
                }
            }

            async saveAnalytics() {
                try {
                    const analytics = {
                        sessionId: this.sessionId,
                        timestamp: Date.now(),
                        interactionCount: this.interactionCount,
                        conversationLength: this.conversationHistory.length
                    };
                    
                    fetch('save_chat_analytics.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(analytics)
                    }).catch(error => {
                        console.log('Analytics save failed (non-critical):', error);
                    });
                    
                    console.log('Analytics saved:', analytics);
                } catch (error) {
                    console.error('Error saving analytics:', error);
                }
            }

            clearHistory() {
                this.conversationHistory = [];
                this.interactionCount = 0;
                localStorage.removeItem('polybot_history');
                localStorage.removeItem('polybot_interaction_count');
                console.log('Conversation history cleared');
            }

            findResponse(userMessage) {
                const lowerMessage = userMessage.toLowerCase();
                
                // Check each category for matching patterns
                for (const [category, data] of Object.entries(polyBotKnowledge)) {
                    for (const pattern of data.patterns) {
                        if (lowerMessage.includes(pattern)) {
                            // Return random response from this category
                            const responses = data.responses;
                            return responses[Math.floor(Math.random() * responses.length)];
                        }
                    }
                }
                
                // If no match found, return default response
                const defaultResponses = polyBotKnowledge.default.responses;
                return defaultResponses[Math.floor(Math.random() * defaultResponses.length)];
            }

            generateResponse(userMessage) {
                try {
                    console.log('User Message:', userMessage);
                    
                    // Find appropriate response
                    const response = this.findResponse(userMessage);
                    
                    // Add user message to memory
                    this.addMessage(userMessage, true, 'user_input');
                    
                    console.log('Generated Response:', response);
                    
                    // Add bot response to memory
                    this.addMessage(response, false, 'bot_response');
                    
                    return response;
                } catch (error) {
                    console.error('Response Generation Error:', error);
                    return "Hello! I'm Poly from James Polymer. How can I help you today?";
                }
            }
        }

        // Initialize Simple PolyBot
        const polyBot = new SimplePolyBot();

        // Message sending functionality
        const messageInput = document.querySelector('.message-input');
        const sendBtn = document.querySelector('.send-btn');
        const chatMessages = document.querySelector('.chat-messages');
        const clearHistoryBtn = document.getElementById('clearHistoryBtn');
        
        // Clear History functionality
        clearHistoryBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear your conversation history? This action cannot be undone.')) {
                // Clear the conversation memory
                polyBot.clearHistory();
                
                // Clear the chat display (keep only the initial bot message)
                const chatContent = document.getElementById('chat-content');
                chatContent.innerHTML = `
                    <!-- Bot Message -->
                    <div class="message bot-message" style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px;">
                        <div class="message-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem;">P</div>
                        <div class="message-bubble" style="background: #f8f9fa; padding: 12px 16px; border-radius: 18px; max-width: 70%; color: #495057; line-height: 1.4;">
                            Hello! I am Poly, your chat bot assistant from JPMC.
                        </div>
                    </div>
                `;
                
                // Show success message
                alert('Conversation history cleared successfully!');
            }
        });

        function sendMessage() {
            const message = messageInput.value.trim();
            if (message) {
                // Create user message element
                const userMessage = document.createElement('div');
                userMessage.className = 'message user-message';
                userMessage.style.cssText = 'display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px; justify-content: flex-end;';
                
                userMessage.innerHTML = `
                    <div class="message-bubble" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 16px; border-radius: 18px; max-width: 70%; line-height: 1.4;">
                        ${message}
                    </div>
                    <div class="message-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: #6c757d; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem;">U</div>
                `;
                
                chatMessages.appendChild(userMessage);
                messageInput.value = '';
                
                // Auto-scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Show typing indicator
                const typingIndicator = document.createElement('div');
                typingIndicator.className = 'message bot-message typing';
                typingIndicator.style.cssText = 'display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px;';
                typingIndicator.innerHTML = `
                    <div class="message-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem;">P</div>
                    <div class="message-bubble" style="background: #f8f9fa; padding: 12px 16px; border-radius: 18px; max-width: 70%; color: #495057; line-height: 1.4;">
                        <div style="display: flex; gap: 4px;">
                            <div style="width: 8px; height: 8px; background: #667eea; border-radius: 50%; animation: typing 1.4s infinite ease-in-out;"></div>
                            <div style="width: 8px; height: 8px; background: #667eea; border-radius: 50%; animation: typing 1.4s infinite ease-in-out 0.2s;"></div>
                            <div style="width: 8px; height: 8px; background: #667eea; border-radius: 50%; animation: typing 1.4s infinite ease-in-out 0.4s;"></div>
                        </div>
                    </div>
                `;
                chatMessages.appendChild(typingIndicator);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Generate Simple PolyBot response
                let aiResponse;
                try {
                    aiResponse = polyBot.generateResponse(message);
                } catch (error) {
                    console.error('PolyBot Response Error:', error);
                    const fallbacks = [
                        "Hi there! 👋 I'm Poly from James Polymer. How can I help you today?",
                        "Hello! 😊 I'm Poly, your AI assistant. What can I help you with?",
                        "Hey! 👋 I'm Poly from James Polymer. How can I assist you?",
                        "Hi! 😄 I'm Poly, your friendly AI assistant. What would you like to know?"
                    ];
                    aiResponse = fallbacks[Math.floor(Math.random() * fallbacks.length)];
                }
                
                // Remove typing indicator and show AI response after 300-600ms
                setTimeout(() => {
                    try {
                        chatMessages.removeChild(typingIndicator);
                    } catch (error) {
                        console.error('Error removing typing indicator:', error);
                    }
                    
                    const botMessage = document.createElement('div');
                    botMessage.className = 'message bot-message';
                    botMessage.style.cssText = 'display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px;';
                    
                    botMessage.innerHTML = `
                        <div class="message-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem;">P</div>
                        <div class="message-bubble" style="background: #f8f9fa; padding: 12px 16px; border-radius: 18px; max-width: 70%; color: #495057; line-height: 1.4;">
                            ${aiResponse}
                        </div>
                    `;
                    
                    chatMessages.appendChild(botMessage);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }, 300 + Math.random() * 300); // Random delay between 300-600ms
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        function attachSuggestedButtonListeners() {
            const suggestedButtons = document.querySelectorAll('.suggested-question');
            suggestedButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const message = this.textContent.trim();
                    messageInput.value = message;
                    sendBtn.click();
                });
            });
        }

        attachSuggestedButtonListeners();

        const suggestedContainer = document.getElementById('suggested-questions');

        suggestedContainer.addEventListener('wheel', function (e) {
            if (e.deltaY !== 0) {
                e.preventDefault();
                suggestedContainer.scrollLeft += e.deltaY;
            }
        }, { passive: false });

    });
    </script>
<script>
    const tabButtons = document.querySelectorAll('.tab-btn');
    const chatTab = document.getElementById('chat-tab');
    const connectTab = document.getElementById('connect-tab');
    const faqTab = document.getElementById('faq-tab');

    tabButtons.forEach((btn, index) => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            chatTab.style.display = 'none';
            connectTab.style.display = 'none';
            faqTab.style.display = 'none';

            if (index === 0) chatTab.style.display = 'block';
            if (index === 1) connectTab.style.display = 'block';
            if (index === 2) faqTab.style.display = 'block';
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('faqSearchInput');
    const faqItems = document.querySelectorAll('.faq-item');

    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase().trim();

        // Get all keywords (even short ones), filter out empty strings
        const keywords = searchTerm
            .split(/\s+/)
            .filter(word => word.length > 0);

        faqItems.forEach(item => {
            const questionEl = item.querySelector('h4');
            const answerEl = item.querySelector('p');

            const originalQuestion = questionEl.textContent;
            const originalAnswer = answerEl.textContent;

            const combined = (originalQuestion + ' ' + originalAnswer).toLowerCase();
            const isMatch = keywords.length === 0 || keywords.some(keyword => combined.includes(keyword));

            if (isMatch) {
                item.style.display = 'block';

                const highlight = (text) => {
                    let escaped = text.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    if (keywords.length === 0) return escaped;

                    const pattern = keywords
                        .map(k => k.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')) // Escape special regex characters
                        .join('|');
                    
                    const regex = new RegExp(`(${pattern})`, 'gi');
                    return escaped.replace(regex, '<mark>$1</mark>');
                };

                questionEl.innerHTML = highlight(originalQuestion);
                answerEl.innerHTML = highlight(originalAnswer);
            } else {
                item.style.display = 'none';
                questionEl.textContent = originalQuestion;
                answerEl.textContent = originalAnswer;
            }
        });
    });
});
</script>

</body>

</html> 

