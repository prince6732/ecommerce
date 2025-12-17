'use client';

import { useState, useEffect } from 'react';
import {
    Mail,
    Search,
    Trash2,
    Eye,
    Phone,
    Calendar,
    CheckCircle2,
    Loader2
} from 'lucide-react';
import {
    deleteContactMessage,
    getContactMessages,
    markMessageAsRead,
    type ContactMessage
} from '../../../../../utils/contactUsApi';
import Modal from '@/components/(sheared)/Modal';
import { useLoader } from '@/context/LoaderContext';
import ErrorMessage from '@/components/(sheared)/ErrorMessage';
import SuccessMessage from '@/components/(sheared)/SuccessMessage';

export default function ContactMessagesPage() {
    const [messages, setMessages] = useState<ContactMessage[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [selectedMessage, setSelectedMessage] = useState<ContactMessage | null>(null);
    const [showModal, setShowModal] = useState(false);
    const [messageToDelete, setMessageToDelete] = useState<ContactMessage | null>(
        null
    );
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const { showLoader, hideLoader } = useLoader();

    useEffect(() => {
        fetchMessages();
    }, [currentPage, searchTerm]);

    const fetchMessages = async () => {
        try {
            setLoading(true);
            const response = await getContactMessages(currentPage, searchTerm);
            setMessages(response.data.data);
            setTotalPages(response.data.last_page);
        } catch (error) {
            console.error('Failed to fetch messages:', error);
            setErrorMessage("Failed to load contact messages. Please try again.");
        } finally {
            setLoading(false);
        }
    };

    const confirmDelete = (message: ContactMessage) => {
        setMessageToDelete(message);
        setIsDeleteModalOpen(true);
    };

    const handleDelete = async () => {
        if (!messageToDelete) return;
        showLoader();
        try {
            await deleteContactMessage(messageToDelete.id);
            setSuccessMessage("Message deleted successfully!");
            fetchMessages();
        } catch {
            setErrorMessage("Failed to delete message");
        } finally {
            hideLoader();
            setIsDeleteModalOpen(false);
            setMessageToDelete(null);
        }
    };

    const handleView = async (message: ContactMessage) => {
        setSelectedMessage(message);
        setShowModal(true);

        if (!message.is_read) {
            try {
                await markMessageAsRead(message.id);
                fetchMessages();
            } catch (error) {
                console.error('Failed to mark as read:', error);
            }
        }
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        setCurrentPage(1);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <div className="p-6">
            {errorMessage && <ErrorMessage message={errorMessage} onClose={() => setErrorMessage(null)} />}
            {successMessage && <SuccessMessage message={successMessage} onClose={() => setSuccessMessage(null)} />}
            {/* Header */}
            {/* Header + Search */}
         <div className="p-5 bg-white/70 backdrop-blur border border-gray-200 rounded-2xl shadow-lg mb-6">

  {/* Top Row */}
  <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

    {/* Title */}
    <div className="px-5">
      <h1 className="lg:text-3xl text-xl font-bold text-gray-900 tracking-tight">
        Contact Messages
      </h1>
      <p className="text-sm text-gray-600 mt-1">
        Manage customer inquiries and support requests
      </p>
    </div>

    {/* Search Bar */}
    <form
      onSubmit={handleSearch}
      className="
        mt-4 lg:mt-0 px-5
        flex flex-col sm:flex-row gap-3
        w-full
        lg:max-w-[650px]
        xl:max-w-[800px]
      "
    >
      {/* Input */}
      <div className="relative flex-[3]">
        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          type="text"
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          placeholder="Search by name, email, phone, or message..."
          className="
            w-full pl-11 pr-4 py-3
            border border-gray-300 rounded-xl
            focus:outline-none focus:ring-2 focus:ring-orange-400
            transition
          "
        />
      </div>

      {/* Button */}
      <button
        type="submit"
        className="
          flex items-center justify-center gap-2
          px-6 py-3
          bg-gradient-to-r from-orange-400 to-yellow-400
          hover:from-orange-500 hover:to-yellow-500
          text-white font-semibold
          rounded-xl shadow-md hover:shadow-lg
          transition-all duration-200
        "
      >
        Search
      </button>
    </form>

  </div>
</div>



            {/* Messages Table */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {loading ? (
                    <div className="flex items-center justify-center py-12">
                        <Loader2 className="w-8 h-8 animate-spin text-orange-500" />
                    </div>
                ) : messages.length === 0 ? (
                    <div className="text-center py-12">
                        <Mail className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                        <p className="text-gray-500 text-lg">No messages found</p>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Phone
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {messages.map((message) => (
                                        <tr key={message.id} className={`hover:bg-gray-50 transition-colors ${!message.is_read ? 'bg-orange-50/30' : ''}`}>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {message.is_read ? (
                                                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                                        <CheckCircle2 className="w-3 h-3" />
                                                        Read
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-700 text-xs rounded-full font-medium">
                                                        <Mail className="w-3 h-3" />
                                                        New
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">{message.name}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-600">{message.email}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-600">{message.phone_number}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-600">{formatDate(message.created_at)}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center gap-2">
                                                    <button
                                                        onClick={() => handleView(message)}
                                                        className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                        title="View message"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => confirmDelete(message)}
                                                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                        title="Delete message"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {totalPages > 1 && (
                            <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                <div className="text-sm text-gray-600">
                                    Page {currentPage} of {totalPages}
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                                        disabled={currentPage === 1}
                                        className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                                        disabled={currentPage === totalPages}
                                        className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            <Modal
                isOpen={showModal && !!selectedMessage}
                onClose={() => {
                    setShowModal(false);
                    setSelectedMessage(null);
                }}
                title="Message Details"
                width="max-w-2xl"
            >
                {selectedMessage && (
                    <div className="p-6 space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium text-gray-500 mb-1 block">Name</label>
                                <p className="text-gray-900 font-medium">{selectedMessage?.name}</p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-500 mb-1 block">Status</label>
                                <span className={`inline-flex items-center gap-1 px-2 py-1 ${selectedMessage?.is_read
                                    ? 'bg-gray-100 text-gray-700'
                                    : 'bg-orange-100 text-orange-700'
                                    } text-xs rounded-full`}
                                >
                                    {selectedMessage?.is_read
                                        ? <CheckCircle2 className="w-3 h-3" />
                                        : <Mail className="w-3 h-3" />}
                                    {selectedMessage?.is_read ? 'Read' : 'New'}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-500 mb-1 flex items-center gap-2">
                                <Mail className="w-4 h-4" /> Email
                            </label>
                            <p className="text-gray-900">{selectedMessage?.email}</p>
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-500 mb-1 flex items-center gap-2">
                                <Phone className="w-4 h-4" /> Phone Number
                            </label>
                            <p className="text-gray-900">{selectedMessage?.phone_number}</p>
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-500 mb-1 flex items-center gap-2">
                                <Calendar className="w-4 h-4" /> Received
                            </label>
                            <p className="text-gray-900">{formatDate(selectedMessage?.created_at)}</p>
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-500 mb-2 block">Message</label>
                            <div className="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                <p className="text-gray-700 whitespace-pre-wrap leading-relaxed">
                                    {selectedMessage?.message}
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-3 pt-4">
                            <button
                                onClick={() => {
                                    setShowModal(false);
                                    setSelectedMessage(null);
                                }}
                                className="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                            >
                                Close
                            </button>

                            <button
                                onClick={() => {
                                    handleDelete();
                                    setShowModal(false);
                                }}
                                className="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors font-medium flex items-center justify-center gap-2"
                            >
                                <Trash2 className="w-4 h-4" />
                                Delete
                            </button>
                        </div>
                    </div>
                )}
            </Modal>

            {/* Delete Confirm Modal */}
            <Modal
                width="max-w-xl"
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                title="Confirm Delete"
            >
                <p className="text-gray-900">Are you sure you want to delete this message?</p>
                <div className="mt-4 flex justify-end space-x-4">
                    <button
                        onClick={() => setIsDeleteModalOpen(false)}
                        className="rounded bg-gray-500 px-4 py-2 text-white"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleDelete}
                        className="rounded bg-red-500 px-4 py-2 text-white"
                    >
                        Delete
                    </button>
                </div>
            </Modal>
        </div>
    );
}
