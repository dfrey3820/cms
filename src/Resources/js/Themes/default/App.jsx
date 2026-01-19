import React from 'react';

export default function App({ children }) {
    return (
        <div>
            <header>
                <h1>Default Theme</h1>
            </header>
            <main>
                {children}
            </main>
            <footer>
                <p>Footer</p>
            </footer>
        </div>
    );
}