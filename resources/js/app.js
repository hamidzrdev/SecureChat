import './echo';

/**
 * Realtime bridge example for Livewire + Echo/Reverb.
 *
 * Requires `window.Echo` to be available (from resources/js/echo.js or any
 * equivalent bootstrap). When events are received, this bridge dispatches
 * Livewire events that server-side Livewire components can listen for.
 */
(function bootstrapChatRealtimeBridge() {
    const state = {
        userId: null,
        conversationId: null,
    };

    const dispatchToLivewire = (eventName, payload) => {
        if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
            window.Livewire.dispatch(eventName, { payload });
        }
    };

    const canSubscribe = () => Boolean(window.Echo);

    const leaveUserChannel = () => {
        if (!canSubscribe() || !state.userId) {
            return;
        }

        window.Echo.leave(`private-user.${state.userId}`);
    };

    const leaveConversationChannel = () => {
        if (!canSubscribe() || !state.conversationId) {
            return;
        }

        window.Echo.leave(`private-conversation.${state.conversationId}`);
    };

    const joinUserChannel = (userId) => {
        window.Echo.private(`private-user.${userId}`)
            .listen('.PrivateChatOpened', (eventPayload) => {
                dispatchToLivewire('reverb-private-chat-opened', eventPayload);
            })
            .listen('.PrivateMessageReceived', (eventPayload) => {
                dispatchToLivewire('reverb-private-message-received', eventPayload);
            });
    };

    const joinConversationChannel = (conversationId) => {
        window.Echo.private(`private-conversation.${conversationId}`)
            .listen('.MessageSent', (eventPayload) => {
                dispatchToLivewire('reverb-message-sent', eventPayload);
            });
    };

    window.SChatRealtime = {
        configure({ userId = null, conversationId = null } = {}) {
            if (!canSubscribe()) {
                return;
            }

            const nextUserId = Number(userId) || null;
            const nextConversationId = Number(conversationId) || null;

            if (state.userId !== nextUserId) {
                leaveUserChannel();
                state.userId = nextUserId;

                if (state.userId) {
                    joinUserChannel(state.userId);
                }
            }

            if (state.conversationId !== nextConversationId) {
                leaveConversationChannel();
                state.conversationId = nextConversationId;

                if (state.conversationId) {
                    joinConversationChannel(state.conversationId);
                }
            }
        },

        reset() {
            leaveConversationChannel();
            leaveUserChannel();
            state.userId = null;
            state.conversationId = null;
        },
    };
})();
