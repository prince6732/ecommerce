"use client";

import { useEffect, useState, useRef } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import { AxiosError } from "axios";
import Link from "next/link";
import logo from "@/public/E-Com Array.png";
import Image from "next/image";
import { Mail, ArrowLeft, CheckCircle2, RefreshCw } from "lucide-react";
import { useLoader } from "@/context/LoaderContext";
import ErrorMessage from "@/components/(sheared)/ErrorMessage";
import SuccessMessage from "@/components/(sheared)/SuccessMessage";
import axios from "../../../../utils/axios";

export default function EmailVerifyPage() {
  const [email, setEmail] = useState<string | null>(null);
  const [otp, setOtp] = useState(["", "", "", "", "", ""]);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [isResending, setIsResending] = useState(false);
  const [resendCountdown, setResendCountdown] = useState(0);
  const inputRefs = useRef<(HTMLInputElement | null)[]>([]);
  const router = useRouter();
  const { user, loading, setUserDirectly } = useAuth();
  const { showLoader, hideLoader } = useLoader();

  useEffect(() => {
    if (!loading && user) {
      router.replace("/");
    }
  }, [user, loading, router]);

  useEffect(() => {
    const savedEmail = localStorage.getItem("verifyEmail");
    setEmail(savedEmail);
    if (!savedEmail) {
      setErrorMessage("No email found. Please register first.");
    }
  }, []);

  useEffect(() => {
    if (resendCountdown > 0) {
      const timer = setTimeout(() => setResendCountdown(resendCountdown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [resendCountdown]);

  const handleOtpChange = (index: number, value: string) => {
    if (value.length > 1) {
      value = value[0];
    }

    if (!/^\d*$/.test(value)) return;

    const newOtp = [...otp];
    newOtp[index] = value;
    setOtp(newOtp);

    if (value && index < 5) {
      inputRefs.current[index + 1]?.focus();
    }
  };

  const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Backspace" && !otp[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
  };

  const handlePaste = (e: React.ClipboardEvent) => {
    e.preventDefault();
    const pastedData = e.clipboardData.getData("text").slice(0, 6);
    if (!/^\d+$/.test(pastedData)) return;

    const newOtp = [...otp];
    pastedData.split("").forEach((char, index) => {
      if (index < 6) newOtp[index] = char;
    });
    setOtp(newOtp);

    const nextEmptyIndex = newOtp.findIndex((val) => !val);
    const focusIndex = nextEmptyIndex === -1 ? 5 : nextEmptyIndex;
    inputRefs.current[focusIndex]?.focus();
  };

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!email) {
      setErrorMessage("Email not found. Please register first.");
      return;
    }

    const otpCode = otp.join("");
    if (otpCode.length !== 6) {
      setErrorMessage("Please enter all 6 digits of the OTP.");
      return;
    }

    showLoader();
    try {
      setErrorMessage(null);
      setSuccessMessage(null);

      const response = await axios.post("/api/verify-otp", {
        email,
        otp: otpCode,
      });

      setSuccessMessage("Email verified successfully! Redirecting to home page...");
      localStorage.removeItem("verifyEmail");

      if (response.data.token && response.data.user) {
        localStorage.setItem("token", response.data.token);
        localStorage.setItem("user", JSON.stringify(response.data.user));
        setUserDirectly(response.data.user);

        setTimeout(() => {
          router.push("/");
        }, 1500);
      } else {
        setTimeout(() => {
          router.push("/login");
        }, 2000);
      }
    } catch (err) {
      const error = err as AxiosError<{ message?: string; expired?: boolean }>;
      const errorMsg = error.response?.data?.message || "Verification failed. Please try again.";
      setErrorMessage(errorMsg);

      if (error.response?.data?.expired) {
        setOtp(["", "", "", "", "", ""]);
      }
    } finally {
      hideLoader();
    }
  };

  const handleResendOTP = async () => {
    if (!email) {
      setErrorMessage("Email not found. Please register first.");
      return;
    }

    setIsResending(true);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await axios.post("/api/resend-otp", { email });
      setSuccessMessage("A new OTP has been sent to your email!");
      setOtp(["", "", "", "", "", ""]);
      setResendCountdown(60);
      inputRefs.current[0]?.focus();
    } catch (err) {
      const error = err as AxiosError<{ message?: string; retry_after?: number }>;
      const errorMsg = error.response?.data?.message || "Failed to resend OTP. Please try again.";
      setErrorMessage(errorMsg);

      if (error.response?.data?.retry_after) {
        setResendCountdown(error.response.data.retry_after);
      }
    } finally {
      setIsResending(false);
    }
  };

  if (loading || (user && user.email_verified_at)) {
    return (
      <div className="w-screen h-screen flex items-center justify-center bg-gradient-to-br from-orange-50 to-orange-100">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-orange-500 mx-auto"></div>
          <p className="text-gray-600 text-xl mt-4">Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <section className="min-h-screen flex items-center justify-center bg-gradient-to-br from-orange-50 via-white to-orange-50 px-4 py-10">
      <div className="w-full max-w-md">
        {/* Back Button */}
        <Link
          href="/register"
          className="inline-flex items-center gap-2 text-gray-600 hover:text-orange-500 transition mb-4"
        >
          <ArrowLeft size={20} />
          <span>Back to Register</span>
        </Link>

        {/* Main Card */}
        <div className="bg-white rounded-3xl shadow-2xl p-8 sm:p-10 border border-orange-100">
          {/* Logo & Title */}
          <div className="text-center mb-8">
            {logo && <Image src={logo} unoptimized alt="E-Com Array Logo" className="w-32 mx-auto mb-4" />}
            <div className="w-20 h-20 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center mx-auto mb-4 shadow-lg">
              <Mail className="text-white" size={36} />
            </div>
            <h1 className="text-gray-800 font-extrabold text-3xl mb-2">
              Verify Your Email
            </h1>
            <p className="text-gray-500 text-sm leading-relaxed">
              We've sent a 6-digit OTP to
              <br />
              <span className="text-orange-600 font-semibold">{email || "your email"}</span>
            </p>
          </div>

          {errorMessage && <ErrorMessage message={errorMessage} onClose={() => setErrorMessage(null)} />}
          {successMessage && <SuccessMessage message={successMessage} onClose={() => setSuccessMessage(null)} />}

          {/* OTP Form */}
          <form onSubmit={onSubmit} className="space-y-6">
            {/* OTP Input */}
            <div>
              <label className="text-gray-700 font-semibold mb-3 block text-center">
                Enter Verification Code
              </label>
              <div className="flex gap-2 justify-center" onPaste={handlePaste}>
                {otp.map((digit, index) => (
                  <input
                    key={index}
                    ref={(el) => { inputRefs.current[index] = el; }}
                    type="text"
                    inputMode="numeric"
                    maxLength={1}
                    value={digit}
                    onChange={(e) => handleOtpChange(index, e.target.value)}
                    onKeyDown={(e) => handleKeyDown(index, e)}
                    className="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200 transition"
                    autoFocus={index === 0}
                  />
                ))}
              </div>
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              className="w-full h-14 rounded-xl font-semibold bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700 hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <CheckCircle2 size={20} />
              <span>Verify Email</span>
            </button>
          </form>

          {/* Resend OTP */}
          <div className="mt-6 text-center">
            {resendCountdown > 0 ? (
              <p className="text-sm text-gray-500">
                Resend OTP in <span className="font-bold text-orange-600">{resendCountdown}s</span>
              </p>
            ) : (
              <button
                onClick={handleResendOTP}
                disabled={isResending}
                className="text-sm text-orange-600 hover:text-orange-700 font-semibold hover:underline disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 mx-auto"
              >
                <RefreshCw size={16} className={isResending ? "animate-spin" : ""} />
                <span>{isResending ? "Sending..." : "Resend OTP"}</span>
              </button>
            )}
          </div>

          {/* Divider */}
          <div className="flex items-center my-6">
            <div className="flex-1 border-t border-gray-200"></div>
            <span className="px-4 text-xs text-gray-400 uppercase tracking-wider">Or</span>
            <div className="flex-1 border-t border-gray-200"></div>
          </div>

          {/* Footer */}
          <div className="text-center space-y-2">
            <p className="text-sm text-gray-600">
              Already verified?{" "}
              <Link href="/login" className="text-orange-600 font-semibold hover:underline">
                Login here
              </Link>
            </p>
            <p className="text-xs text-gray-400">
              Didn't receive the code? Check your spam folder or{" "}
              <button
                onClick={handleResendOTP}
                disabled={resendCountdown > 0}
                className="text-orange-600 hover:underline disabled:opacity-50"
              >
                click here
              </button>
            </p>
          </div>
        </div>

        {/* Info Box */}
        <div className="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
          <p className="font-semibold mb-1">💡 Tips:</p>
          <ul className="list-disc list-inside space-y-1 text-xs">
            <li>OTP is valid for 10 minutes</li>
            <li>Check your spam folder if you don't see the email</li>
            <li>Make sure you entered the correct email address</li>
          </ul>
        </div>
      </div>
    </section>
  );
}


