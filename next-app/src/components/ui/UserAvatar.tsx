import React, { useState } from 'react';
import { User } from 'lucide-react';
import { getImageUrl } from '../../../utils/imageUtils';

interface UserAvatarProps {
  name: string;
  profilePicture?: string | null;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
  showBorder?: boolean;
}

const UserAvatar: React.FC<UserAvatarProps> = ({ 
  name, 
  profilePicture, 
  size = 'md',
  className = '',
  showBorder = false
}) => {
  const [imageError, setImageError] = useState(false);
  const [imageLoading, setImageLoading] = useState(true);
  const sizeClasses = {
    sm: 'w-8 h-8 text-xs',
    md: 'w-12 h-12 text-lg',
    lg: 'w-16 h-16 text-xl'
  };

  const iconSizes = {
    sm: 'w-4 h-4',
    md: 'w-6 h-6',
    lg: 'w-8 h-8'
  };

  const getInitials = (name: string): string => {
    return name
      .split(' ')
      .map(word => word.charAt(0))
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const borderClass = showBorder ? 'ring-2 ring-white shadow-md' : '';
  const baseClasses = `${sizeClasses[size]} rounded-full flex items-center justify-center font-bold ${borderClass} ${className}`;
  const imageUrl = getImageUrl(profilePicture);

  if (imageUrl && !imageError) {
    return (
      <div className={`${baseClasses} overflow-hidden bg-gray-200 relative`}>
        {imageLoading && (
          <div className="absolute inset-0 flex items-center justify-center bg-gray-100">
            <div className="w-4 h-4 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin"></div>
          </div>
        )}
        <img
          src={imageUrl}
          alt={`${name}'s profile picture`}
          className={`w-full h-full object-cover transition-opacity duration-200 ${imageLoading ? 'opacity-0' : 'opacity-100'}`}
          onLoad={() => setImageLoading(false)}
          onError={() => {
            setImageError(true);
            setImageLoading(false);
          }}
        />
      </div>
    );
  }

  // Generate consistent color based on name for better UX
  const getGradientFromName = (name: string): string => {
    const gradients = [
      'from-blue-500 to-purple-600',
      'from-green-500 to-teal-600',
      'from-purple-500 to-pink-600',
      'from-orange-500 to-red-600',
      'from-indigo-500 to-blue-600',
      'from-emerald-500 to-cyan-600',
      'from-rose-500 to-orange-600',
      'from-violet-500 to-indigo-600'
    ];
    
    // Generate consistent index based on name
    const index = name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % gradients.length;
    return gradients[index];
  };

  // Fallback to initials or user icon
  return (
    <div className={`${baseClasses} bg-gradient-to-br ${getGradientFromName(name)} text-white`}>
      {name ? (
        getInitials(name)
      ) : (
        <User className={iconSizes[size]} />
      )}
    </div>
  );
};

export default UserAvatar;