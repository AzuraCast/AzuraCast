<template>
  <div class="resonance-container">
    <header class="signal-header">
      <div class="branding-wrap">
        <span class="element-tag">ELEMENT: AIR (O)</span>
        <h1 class="slot-title">SLOT I: OPENING RESONANCE</h1>
      </div>
      <div class="tuk-tuk-logo"></div>
    </header>

    <main class="signal-display">
      <div class="status-stack">
        <p class="glitch-text">STATUS: TRANSMITTING FROM HYPERION</p>
        <p>PHASE: ALIGNMENT INITIATED</p>
      </div>
      
      <div class="signal-visualizer">
        <div v-for="i in 12" :key="i" class="signal-bar"></div>
      </div>
    </main>

    <footer class="doctrine-footer">
      <p>OBSERVE // RECORD // CLASSIFY // PRESERVE</p>
    </footer>
  </div>
</template>

<style scoped>
.resonance-container {
  background-color: #0a0a0a; /* Hadron Black */
  color: #d4d4d4; /* Distressed Silver */
  font-family: 'Courier New', monospace;
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 3rem;
  border: 1px solid #333;
}

.signal-header {
  border-bottom: 2px solid #ff0000; /* Tuk-Tuk Ignition Red */
  padding-bottom: 1rem;
  display: flex;
  justify-content: space-between;
}

.slot-title {
  color: #ff0000; /* Ignition Red */
  font-family: 'Impact', sans-serif;
  font-size: 2.5rem;
  margin-top: 0.5rem;
}

.element-tag { 
  color: #00ccff; /* Tuk-Tuk Signal Cyan */
  font-weight: bold;
  letter-spacing: 3px;
}

.signal-bar {
  width: 10px;
  height: 20%;
  background-color: #00ccff; /* Signal Cyan */
  animation: pulse 1.2s infinite ease-in-out;
}

@keyframes pulse {
  0%, 100% { height: 20%; opacity: 0.4; }
  50% { height: 90%; opacity: 1; }
}

.doctrine-footer {
  border-top: 1px solid #333;
  padding-top: 1rem;
  font-size: 0.7rem;
  letter-spacing: 5px;
  text-align: center;
}
</style>