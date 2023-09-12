<script setup>
import { Head, useForm } from "@inertiajs/vue3";
const form = useForm({
    files: null,
});

function submit() {
    form.post(route("embeddings.store"));
}
</script>
<template>
    <Head title="Embeddings - Add power to the chatbot"></Head>
    <div class="min-h-screen flex flex-col border shadow-md bg-white p-4">
        <form @submit.prevent="submit" method="post">
            <div>
                <label
                    for="image"
                    class="block text-sm text-gray-500 dark:text-gray-300"
                    >Embedding File</label
                >
                <input
                    @input="form.files = $event.target.files"
                    type="file"
                    class="block w-full px-3 py-2 mt-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg file:bg-gray-200 file:text-gray-700 file:text-sm file:px-4 file:py-1 file:border-none file:rounded-full dark:file:bg-gray-800 dark:file:text-gray-200 dark:text-gray-300 placeholder-gray-400/70 dark:placeholder-gray-500 focus:border-blue-400 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-40 dark:border-gray-600 dark:bg-gray-900 dark:focus:border-blue-300"
                />
            </div>
            <button class="px-6 py-2 rounded">Upload</button>
        </form>
    </div>
</template>
