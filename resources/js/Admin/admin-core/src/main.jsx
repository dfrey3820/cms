import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './styles.css';

const el = document.getElementById('admin-core-root');
if (el) {
  createRoot(el).render(<App />);
}

export default App;
