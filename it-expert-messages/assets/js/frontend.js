(function($) {
    // IT Expert Messages JS
    const App = {
        init: function() {
            this.bindEvents();
            if ($('#it-expert-messages-app').length) {
                this.loadConversations();
            }
        },
        bindEvents: function() {
            // Bind send button, etc.
        },
        loadConversations: function() {
            $.ajax({
                url: ITExpertMessages.api_url + '/messages/conversations',
                method: 'GET',
                beforeSend: function ( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', ITExpertMessages.nonce );
                },
                success: function(data) {
                    console.log('Conversations:', data);
                    // Render list (Simplistic implementation)
                    const list = data.map(c => `
                        <div class="conversation-item" data-id="${c.id}">
                            From: ${c.participant_ids}<br>
                            Last: ${c.last_message}
                        </div>
                    `).join('');
                    $('#it-expert-messages-app').html(list);
                }
            });
        }
    };
    $(document).ready(function() { App.init(); });
})(jQuery);
