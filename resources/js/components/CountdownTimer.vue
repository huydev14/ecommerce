<template>
  <div v-if="isCountingDown" class="countdown-timer">
    <span class="timer-text">Thử lại trong {{ formattedTime }}</span>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  seconds: {
    type: Number,
    required: true,
    default: 0
  }
});

const remainingSeconds = ref(props.seconds);
let intervalId = null;

const isCountingDown = computed(() => remainingSeconds.value > 0);

const formattedTime = computed(() => {
  const minutes = Math.floor(remainingSeconds.value / 60);
  const secs = remainingSeconds.value % 60;
  
  if (minutes > 0) {
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
  }
  return `${secs}s`;
});

const startCountdown = () => {
  if (intervalId) clearInterval(intervalId);
  
  intervalId = setInterval(() => {
    if (remainingSeconds.value > 0) {
      remainingSeconds.value--;
    } else {
      clearInterval(intervalId);
    }
  }, 1000);
};

watch(() => props.seconds, (newValue) => {
  remainingSeconds.value = newValue;
  if (newValue > 0) {
    startCountdown();
  }
});

onMounted(() => {
  remainingSeconds.value = props.seconds;
  if (remainingSeconds.value > 0) {
    startCountdown();
  }
});

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId);
  }
});
</script>

<style scoped>
.countdown-timer {
  display: inline-block;
  margin-top: 0.5rem;
  padding: 0.5rem 0.75rem;
  background-color: rgba(255, 193, 7, 0.1);
  border-radius: 0.25rem;
  border-left: 3px solid #ffc107;
}

.timer-text {
  font-weight: 600;
  color: #ff6b6b;
  font-family: 'Courier New', monospace;
  font-size: 0.95rem;
}
</style>
