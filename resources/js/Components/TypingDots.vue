<template>
    <div class="flex-row-reverse flex items-center mb-4">
        <div class="flex-none flex flex-col items-center space-y-1 mr-4">
            <img class="rounded-full w-10 h-10 mx-4" src="/chatbot.png" />
            <a
                href="#"
                class="block text-xs hover:underline"
                v-text="'Chatbot'"
            ></a>
        </div>
        <div class="p-6 rounded-lg mb-2 relative bg-gray-200 text-gray-600">
            <div class="text-md">
                <div class="container-dot space-x-2">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
.container-dot {
    display: inline-block;
}
.dot {
    height: 10px;
    width: 10px;
    border-radius: 100%;
    display: inline-block;
    background-color: #b4b5b9;
    animation: 1.2s typing-dot ease-in-out infinite;
}
.dot:nth-of-type(2) {
    animation-delay: 0.15s;
}
.dot:nth-of-type(3) {
    animation-delay: 0.25s;
}
@keyframes typing-dot {
    15% {
        transform: translateY(-35%);
        opacity: 0.5;
    }
    30% {
        transform: translateY(0%);
        opacity: 1;
    }
}
</style>
