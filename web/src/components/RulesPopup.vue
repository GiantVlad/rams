<template>
  <div class="rules-overlay" @click.self="$emit('close')">
    <div class="rules-card">
      <header class="rules-header">
        <h2>How to Play Rams</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </header>
      
      <div class="rules-content">
        <section>
          <h3>1. The Goal</h3>
          <p>Each player starts with <strong>20 points</strong>. The goal is to reduce your score to exactly 0 by winning tricks.</p>
        </section>

        <section>
          <h3>2. Dealing & Exchanging</h3>
          <ul>
            <li>Each player receives 5 cards. A trump card is revealed.</li>
            <li>Players can exchange 1-5 cards with the deck or "Pass" to sit out the round and avoid penalties.</li>
          </ul>
        </section>

        <section>
          <h3>3. Playing Tricks</h3>
          <ul>
            <li>You must follow suit if possible.</li>
            <li>If you cannot follow suit, you can play a trump card or any other card.</li>
            <li>The highest card of the led suit, or the highest trump card, wins the trick.</li>
          </ul>
        </section>

        <section>
          <h3>4. Scoring & Penalties</h3>
          <ul>
            <li>Each trick won reduces your score by 1 point.</li>
            <li>If you play in a round but win <strong>0 tricks</strong>, you receive a <strong>+5 point penalty</strong>.</li>
            <li>If you are dealt 5 cards of the same suit, you immediately lose 5 points.</li>
          </ul>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
defineEmits(['close'])
</script>

<style scoped>
.rules-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.rules-card {
  background: var(--bg-panel, #1e293b);
  border: 1px solid var(--border-color, #334155);
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.rules-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: rgba(0, 0, 0, 0.2);
  border-bottom: 1px solid var(--border-color, #334155);
}

.rules-header h2 {
  margin: 0;
  font-size: 20px;
  color: var(--accent-blue, #60a5fa);
}

.close-btn {
  background: none;
  border: none;
  color: var(--text-muted, #94a3b8);
  font-size: 28px;
  cursor: pointer;
  line-height: 1;
  padding: 0;
  transition: color 0.2s;
}

.close-btn:hover {
  color: var(--text-main, #f8fafc);
}

.rules-content {
  padding: 24px;
  overflow-y: auto;
  color: var(--text-main, #f8fafc);
  font-size: 15px;
  line-height: 1.5;
}

.rules-content section {
  margin-bottom: 20px;
}

.rules-content section:last-child {
  margin-bottom: 0;
}

.rules-content h3 {
  font-size: 16px;
  color: var(--accent-blue, #60a5fa);
  margin-top: 0;
  margin-bottom: 8px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  padding-bottom: 6px;
}

.rules-content ul {
  margin: 0;
  padding-left: 20px;
}

.rules-content li {
  margin-bottom: 6px;
  color: var(--text-muted, #cbd5e1);
}

.rules-content li:last-child {
  margin-bottom: 0;
}

.rules-content p {
  color: var(--text-muted, #cbd5e1);
  margin-top: 0;
}
</style>
