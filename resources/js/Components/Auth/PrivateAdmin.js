import { Outlet } from "react-router";
import { Navigate } from 'react-router-dom';

const useAdminAuth = () => {
  const token = localStorage.getItem('admin-token');
  return !!token; // true if token exists
};

const AdminProtectedRoutes = () => {
  const isAuth = useAdminAuth();
  return isAuth ? <Outlet /> : <Navigate replace to="/" />;
};

export default AdminProtectedRoutes;
