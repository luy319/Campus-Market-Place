container.innerHTML = `
        <button id="aiChatToggle" class="btn btn-primary shadow"
            style="position: fixed; bottom: 24px; right: 24px; z-index: 1050; border-radius: 50px; padding: 10px 20px; font-weight: 600;">
            Campus Shopper Helper
        </button>

        <div id="aiChatBox" class="card shadow-lg d-none"
            style="position: fixed; bottom: 80px; right: 24px; width: 360px; max-width: calc(100vw - 48px); z-index: 1050;">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                <span class="fw-bold small">Campus Shopper Helper</span>
                <button id="aiChatClose" type="button" class="btn-close btn-close-white btn-sm" aria-label="Close"></button>
            </div>
            <div id="aiChatMessages" class="card-body p-3" style="height: 300px; overflow-y: auto; font-size: 0.9rem; background: #f8f9fa;">
                <div class="p-2 mb-2 rounded bg-white border">
                    Hello! I am your Campus Shopper Helper. Ask me about campus products, combinations under a GHS budget, or upcoming event stalls. Vendors can also ask for product descriptions or sales summaries.
                </div>
            </div>
            <div class="card-footer bg-white p-2">
                <form id="aiChatForm" class="d-flex gap-2">
                    <input id="aiChatInput" type="text" class="form-control form-control-sm" placeholder="Ask Campus Shopper Helper..." required>
                    <button type="submit" class="btn btn-primary btn-sm">Send</button>
                </form>
            </div>
        </div>
    `;