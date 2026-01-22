import React from 'react';
import { Link } from '@inertiajs/react';

export default function Index({ users, auth }) {
    return (
        <div className="flex h-screen bg-gray-100">
            <div className="w-64 bg-white shadow-lg">
                <div className="p-4">
                    <h2 className="text-xl font-bold text-gray-800">DSC CMS Admin</h2>
                </div>
                {/* Sidebar omitted for brevity - other pages include it */}
            </div>

            <div className="flex-1 p-8">
                <h1 className="text-3xl font-bold text-gray-800 mb-4">Users</h1>
                <div className="mb-4">
                    <Link href="/admin/users/create" className="bg-blue-600 text-white px-4 py-2 rounded">Create User</Link>
                </div>

                <div className="bg-white rounded shadow overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
                                <th className="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {users.data.map(user => (
                                <tr key={user.id}>
                                    <td className="px-6 py-4 whitespace-nowrap">{user.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{user.email}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{(user.roles||[]).map(r=>r.name).join(', ')}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link href={`/admin/users/${user.id}/edit`} className="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                        <form method="POST" action={`/admin/users/${user.id}`} style={{display:'inline'}}>
                                            <input type="hidden" name="_method" value="DELETE" />
                                            <button type="submit" className="text-red-600 hover:text-red-800">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Pagination links would be rendered by the parent via Inertia props */}
            </div>
        </div>
    );
}
