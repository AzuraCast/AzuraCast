# AGENTS.md — Rahdiot Tuk-Tuk Transmission Rules

## C<template>
  <div class="resonance-container">
    <header class="signal-header">
      <span class="element-tag">ELEMENT: AIR (O)</span>
      <h1 class="slot-title">SLOT I: OPENING RESONANCE</h1>
    </header>

    <main class="signal-display">
      <div class="status-stack">
        <p>STATUS: TRANSMITTING FROM HYPERION</p>
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
  padding: 2rem;
}

.element-tag { color: #57a6ff; font-weight: bold; } /* Air Blue */

.signal-visualizer {
  display: flex;
  gap: 4px;
  height: 50px;
  align-items: flex-end;
}

.signal-bar {
  width: 8px;
  height: 20%;
  background-color: #d4d4d4;
  animation: pulse 1.5s infinite ease-in-out;
}

@keyframes pulse {
  0%, 100% { height: 20%; opacity: 0.5; }
  50% { height: 80%; opacity: 1; }
}
</style>