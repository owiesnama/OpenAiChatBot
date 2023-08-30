<script setup>
import { ref, reactive, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import TypingDots from "@/Components/TypingDots.vue";

const props = defineProps(["response"]);
const message = ref("");
const isTyping = ref(false);
const messages = reactive([]);
const sendMessage = () => {
    messages.push({ content: message.value, role: "user" });
    isTyping.value = true;
    useForm({
        prompt: message.value,
        messages,
    }).post(route("chat.store"), {
        onFinish() {
            isTyping.value = false;
        },
    });
    message.value = "";
};
watch(
    () => props.response,
    () => {
        messages.push(props.response);
    }
);
</script>
<template>
    <Head title="Chat"></Head>
    <div class="min-h-screen flex flex-col border shadow-md bg-white p-4">
        <div class="flex-1 px-4 py-4 overflow-y-auto">
            <div
                class="flex items-center mb-4"
                v-for="message in messages"
                :class="message.role != 'user' ? 'flex-row-reverse' : ''"
            >
                <div
                    class="flex-none flex flex-col items-center space-y-1 mr-4"
                >
                    <img
                        class="rounded-full w-10 h-10 mx-4"
                        :src="
                            message.role != 'user'
                                ? '/chatbot.png'
                                : 'https://images.unsplash.com/photo-1491528323818-fdd1faba62cc?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
                        "
                    />
                    <a
                        href="#"
                        class="block text-xs hover:underline"
                        v-text="message.role != 'user' ? 'Chatbot' : 'Student'"
                    ></a>
                </div>
                <div
                    class="p-6 rounded-lg mb-2 relative"
                    :class="
                        message.role != 'user'
                            ? 'bg-gray-200 text-gray-600'
                            : 'bg-gray-500 text-white'
                    "
                >
                    <div class="text-md" v-text="message.content"></div>
                </div>
            </div>
            <TypingDots v-if="isTyping" />

        </div>

        <form @submit.prevent="sendMessage">
            <div class="flex items-center border-t p-2">
                <div class="w-full mx-2">
                    <input
                        v-model="message"
                        class="w-full rounded-full h-12 p-4 border border-gray-200"
                        type="text"
                        placeholder="Tell me how I can help ..."
                        autofocus
                    />
                </div>
                <!-- chat send action -->
                <div>
                    <button
                        class="inline-flex hover:bg-indigo-50 rounded-full p-2"
                        type="submit"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                        </svg>
                    </button>
                </div>
                <!-- end chat send action -->
            </div>
        </form>
    </div>
</template>
