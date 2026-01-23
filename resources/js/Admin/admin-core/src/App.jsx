import React from 'react';
import Header from './components/Header';
import menuItems from './menuItems';

export default function App() {
  return (
    <div className="admin-core-root">
      <div className="flex h-screen bg-gray-100">
        <div className="w-64 bg-white shadow-lg">
          <div className="p-4">
            <h2 className="text-xl font-bold text-gray-800">Admin Core</h2>
          </div>
          <nav className="mt-4">
            <ul>
              {menuItems.map((item) => (
                <li key={item.name}>
                  <a href={item.href} className="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900">
                    {item.name}
                  </a>
                </li>
              ))}
            </ul>
          </nav>
        </div>

        <div className="flex-1 flex flex-col">
          <Header />
          <div className="flex-1 p-8">
            <h1 className="text-3xl font-bold">Admin Core</h1>
            <p className="text-gray-600">This is the shared admin core theme used by packages to extend.</p>
          </div>
        </div>
      </div>
    </div>
  );
}
