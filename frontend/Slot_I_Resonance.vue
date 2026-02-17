<template>
  <div class="resonance-container">
    <div class="alchemical-overlay"></div> <header class="signal-header">
      <div class="branding-wrap">
        <span class="element-tag">ELEMENT: AIR (O)</span>
        <h1 class="slot-title">SLOT I: OPENING RESONANCE</h1>
      </div>
      <div class="tuk-tuk-logo"></div>
    </header>

    <main class="signal-display">
      <div class="status-stack">
        <p class="hero-tagline">STATUS: TRANSMITTING FROM HYPERION</p>
        <p class="hero-subtitle">PHASE: ALIGNMENT INITIATED</p>
      </div>
      
      <div class="portal-container"> <div class="collider-ring"></div>
        <div class="collider-ring"></div>
        <div class="signal-visualizer">
          <div v-for="i in 12" :key="i" class="signal-bar"></div>
        </div>
      </div>
    </main>

    <footer class="doctrine-footer">
      <p>OBSERVE // RECORD // CLASSIFY // PRESERVE</p>
    </footer>
  </div>
</template>

<style scoped>
/* 1. VESSEL & SKIN */
.resonance-container {
  background-color: #0A0A0A; /* Dark Void */
  color: #F9F9F7; /* Neon White */
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: clamp(20px, 5vw, 50px);
  position: relative;
  overflow: hidden;
  font-family: 'Montserrat', sans-serif;
}

.alchemical-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.02);
  backdrop-filter: blur(20px); /* Alchemical Skin blur */
  border: 1px solid rgba(107, 76, 230, 0.2); /* Cosmic Purple Border */
  pointer-events: none;
}

/* 2. SIGNAL HEADER (Tuk-Tuk Synthesis) */
.signal-header {
  border-bottom: 2px solid #FF3366; /* Glitch Red / Ignition Red */
  padding-bottom: 1rem;
  z-index: 10;
  display: flex;
  justify-content: space-between;
}

.slot-title {
  color: #F4D03F; /* Hadron Gold / Tuk-Tuk Yellow */
  font-family: 'Cormorant Garamond', serif;
  font-weight: 300;
  font-size: clamp(1.8rem, 5vw, 3rem);
  letter-spacing: 0.2em;
}

.element-tag { 
  color: #6B4CE6; /* Cosmic Purple */
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

/* 3. THE PORTAL VISUALIZER */
.portal-container {
  position: relative;
  width: 300px;
  height: 300px;
  margin: 0 auto;
  display: flex;
  justify-content: center;
  align-items: center;
}

.collider-ring {
  position: absolute;
  border-radius: 50%;
  border: 1px solid rgba(107, 76, 230, 0.3);
  width: 100%;
  height: 100%;
  animation: portalBreathe 8s ease-in-out infinite;
}

.signal-visualizer {
  display: flex;
  gap: 6px;
  height: 60px;
  align-items: flex-end;
  z-index: 5;
}

.signal-bar {
  width: 8px;
  height: 20%;
  background: linear-gradient(to top, #2D3FE8, #FF3366); /* Cosmic Blue to Glitch Red */
  animation: pulse 1.2s infinite ease-in-out;
}

/* 4. ANIMATIONS */
@keyframes portalBreathe {
  0%, 100% { transform: scale(1); opacity: 0.4; }
  50% { transform: scale(1.1); opacity: 0.8; }
}

@keyframes pulse {
  0%, 100% { height: 20%; opacity: 0.5; }
  50% { height: 90%; opacity: 1; }
}

.doctrine-footer {
  border-top: 1px solid rgba(107, 76, 230, 0.3);
  padding-top: 1.5rem;
  font-size: 0.7rem;
  letter-spacing: 0.4em;
  text-align: center;
  color: rgba(249, 249, 247, 0.5);
}
</style>