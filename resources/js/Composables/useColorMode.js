import { ref, watch, onMounted } from 'vue';

const STORAGE_KEY = 'cctv-color-mode';
const isLight = ref(false);

function apply(light) {
    document.documentElement.classList.toggle('light', light);
}

function init() {
    const saved = localStorage.getItem(STORAGE_KEY);
    isLight.value = saved === 'light';
    apply(isLight.value);
}

function toggle() {
    isLight.value = !isLight.value;
    localStorage.setItem(STORAGE_KEY, isLight.value ? 'light' : 'dark');
    apply(isLight.value);
}

export function useColorMode() {
    onMounted(() => {
        if (document.documentElement.classList.contains('light') !== isLight.value) {
            apply(isLight.value);
        }
    });

    return { isLight, toggle, init };
}
