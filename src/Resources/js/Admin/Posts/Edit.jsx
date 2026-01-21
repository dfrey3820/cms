import React from 'react';
import { useForm } from '@inertiajs/react';

export default function Edit({ post }) {
    const { data, setData, put, errors } = useForm({
        title: post.title,
        slug: post.slug,
        content: post.content,
        excerpt: post.excerpt,
        status: post.status,
        blocks: post.blocks || [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/posts/${post.id}`);
    };

    return (
        <form onSubmit={handleSubmit}>
            <input
                type="text"
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="Title"
            />
            {errors.title && <div>{errors.title}</div>}

            <input
                type="text"
                value={data.slug}
                onChange={e => setData('slug', e.target.value)}
                placeholder="Slug"
            />
            {errors.slug && <div>{errors.slug}</div>}

            <textarea
                value={data.excerpt}
                onChange={e => setData('excerpt', e.target.value)}
                placeholder="Excerpt"
            />

            <textarea
                value={data.content}
                onChange={e => setData('content', e.target.value)}
                placeholder="Content"
            />

            <select
                value={data.status}
                onChange={e => setData('status', e.target.value)}
            >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>

            <button type="submit">Update Post</button>
        </form>
    );
}