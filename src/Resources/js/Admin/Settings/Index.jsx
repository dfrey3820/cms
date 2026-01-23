import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRightOnRectangleIcon, Cog6ToothIcon } from '@heroicons/react/24/outline';
import Header from '@admin-core/components/Header';
import menuItems from '@admin-core/menuItems';

export default function Index({ settings = {}, auth }) {
  const [activeTab, setActiveTab] = useState('site');
  const { data, setData, post, processing, errors, reset } = useForm({});
  const [clientErrors, setClientErrors] = useState({});

  React.useEffect(() => {
    if (settings) {
      const initialData = {};
      Object.keys(settings).forEach(group => {
        if (settings[group]) {
          settings[group].forEach(setting => {
            initialData[setting.key] = setting.value ?? '';
          });
        }
      });
      reset(initialData);
    }
  }, [settings, reset]);

  const tabs = [
    { id: 'site', label: 'Site', icon: 'fas fa-globe' },
    { id: 'system', label: 'System', icon: 'fas fa-cogs' },
    { id: 'server', label: 'Server', icon: 'fas fa-server' },
    { id: 'mail', label: 'Mail', icon: 'fas fa-envelope' },
    { id: 'database', label: 'Database', icon: 'fas fa-database' },
  ];

  const handleInputChange = (key, value) => setData(key, value);

  const validateField = (key, value, setting = {}) => {
    if (setting.required && (!value || String(value).trim() === '')) {
      return 'This field is required.';
    }

    if (/email|mail/.test(key) && value) {
      const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(".+"))@(([^<>()[\]\\.,;:\s@\"]+\.)+[^<>()[\]\\.,;:\s@\"]{2,})$/i;
      if (!re.test(String(value))) return 'Please enter a valid email address.';
    }

    if (/url|uri|link/.test(key) && value) {
      try { new URL(String(value)); } catch (_e) { return 'Please enter a valid URL.'; }
    }

    return null;
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    // client-side validation for visible fields
    const groupSettings = settings[activeTab] || [];
    const nextClientErrors = {};
    groupSettings.forEach(setting => {
      const val = data[setting.key] ?? setting.value ?? '';
      const msg = validateField(setting.key, val, setting);
      if (msg) nextClientErrors[setting.key] = msg;
    });

    if (Object.keys(nextClientErrors).length > 0) {
      setClientErrors(nextClientErrors);
      return;
    }

    post(route('cms.admin.settings.update'), {
      onSuccess: () => {
        // noop for now
      },
    });
  };

  const renderTabContent = () => {
    const groupSettings = settings[activeTab] || [];
    return (
      <div className="space-y-6">
        {groupSettings.map(setting => (
          <div key={setting.key} className="bg-white p-6 rounded-lg shadow-sm border">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              {setting.key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
            </label>
            <input
              type="text"
              value={data[setting.key] ?? setting.value ?? ''}
              onChange={(e) => {
                const val = e.target.value;
                handleInputChange(setting.key, val);
                const msg = validateField(setting.key, val, setting);
                setClientErrors(prev => {
                  const next = { ...prev };
                  if (msg) next[setting.key] = msg; else delete next[setting.key];
                  return next;
                });
              }}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            {clientErrors[setting.key] && <p className="mt-1 text-sm text-red-600">{clientErrors[setting.key]}</p>}
            {errors[setting.key] && <p className="mt-1 text-sm text-red-600">{errors[setting.key]}</p>}
          </div>
        ))}
      </div>
    );
  };

  return (
    <>
      <Head title="Settings" />

      <div className="flex h-screen bg-gray-100">
        <div className="w-64 bg-white shadow-lg">
          <div className="p-4">
            <h2 className="text-xl font-bold text-gray-800">DSC CMS Admin</h2>
          </div>
          <nav className="mt-4">
            <ul>
              {menuItems.map(item => {
                const Icon = item.icon;
                return (
                  <li key={item.name}>
                    <Link href={item.href} className="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900">
                      <Icon className="w-5 h-5 mr-3" />
                      {item.name}
                    </Link>
                  </li>
                );
              })}
            </ul>
          </nav>
          <div className="absolute bottom-0 w-64 p-4">
            <Link href="/admin/logout" method="post" className="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900">
              <ArrowRightOnRectangleIcon className="w-5 h-5 mr-3" />
              Logout
            </Link>
          </div>
        </div>

        <div className="flex-1 flex flex-col">
          <Header auth={auth} />

          <div className="flex-1 p-8">
            <div className="mb-6">
              <h1 className="text-3xl font-bold text-gray-800">Settings</h1>
              <p className="text-gray-600">Configure your CMS settings here.</p>
            </div>

            <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
              <div className="p-6">
                <h2 className="text-2xl font-bold text-gray-900 mb-6">Global Configuration</h2>

                <div className="border-b border-gray-200 mb-6">
                  <nav className="-mb-px flex space-x-8">
                    {tabs.map(tab => (
                      <button
                        key={tab.id}
                        onClick={() => setActiveTab(tab.id)}
                        className={`py-2 px-1 border-b-2 font-medium text-sm ${activeTab === tab.id ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}`}
                      >
                        <i className={`${tab.icon} mr-2`}></i>
                        {tab.label}
                      </button>
                    ))}
                  </nav>
                </div>

                <form onSubmit={handleSubmit}>
                  {renderTabContent()}

                  <div className="mt-6 flex justify-end">
                    <button type="submit" disabled={processing || Object.keys(clientErrors).length > 0} className="text-white font-bold py-2 px-4 rounded disabled:opacity-50" style={{ backgroundColor: '#009cde' }}>
                      {processing ? 'Saving...' : 'Save Settings'}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
import React from 'react';

export default function Index() {
  return <div />;
}
