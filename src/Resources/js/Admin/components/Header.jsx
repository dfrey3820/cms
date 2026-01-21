import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { UserCircleIcon, ChevronDownIcon } from '@heroicons/react/24/outline';

export default function Header({ auth }) {
    const [dropdownOpen, setDropdownOpen] = useState(false);

    return (
        <header className="bg-white shadow-sm border-b">
            <div className="px-8 py-4 flex justify-between items-center">
                <div className="flex-1"></div>
                <div className="flex items-center space-x-4">
                    <div className="relative">
                        <button
                            onClick={() => setDropdownOpen(!dropdownOpen)}
                            className="flex items-center space-x-2 text-gray-700 hover:text-gray-900"
                        >
                            <UserCircleIcon className="w-8 h-8" />
                            <span>{auth?.user?.name || 'Admin'}</span>
                            <ChevronDownIcon className="w-4 h-4" />
                        </button>
                        {dropdownOpen && (
                            <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                <Link
                                    href="/admin/profile"
                                    className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    onClick={() => setDropdownOpen(false)}
                                >
                                    Profile
                                </Link>
                                <Link
                                    href="/admin/logout"
                                    method="post"
                                    className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    onClick={() => setDropdownOpen(false)}
                                >
                                    Logout
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </header>
    );
}