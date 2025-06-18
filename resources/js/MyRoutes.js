import React, { lazy } from "react";

import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import { positions, Provider } from "react-alert";
import AlertTemplate from "react-alert-template-basic";
import TimeAgo from "javascript-time-ago";
import en from "javascript-time-ago/locale/en.json";

import "./Assets/css/admin.css";
// import './Assets/css/rtl.css';
import "./Assets/css/responsive.css";
import "react-super-responsive-table/dist/SuperResponsiveTableStyle.css";

// Protected Routes
import AdminProtectedRoutes from "./Components/Auth/PrivateAdmin";
import ClientProtectedRoutes from "./Components/Auth/PrivateClient";
import WorkerProtectedRoutes from "./Components/Auth/PrivateWorker";

// Client Routes
import Client from "./Client/Client";
import ClientDashboard from "./Client/ClientDashboard";
import ClientSchedules from "./Client/Pages/Schedule/Schedule";
import ClientOffers from "./Client/Pages/OfferPrice/OfferPrice";
import ClientViewOffer from "./Client/Pages/OfferPrice/ViewOffer";
import ClientContracts from "./Client/Pages/Contract/Contract";
import ClientViewContract from "./Client/Pages/Contract/ViewContract";
import ClientFiles from "./Client/Pages/Schedule/Files";
import ClientJobs from "./Client/Pages/Jobs/TotalJobs";
import ClientJobView from "./Client/Pages/Jobs/ViewJob";
import ClientSetting from "./Client/Pages/Settings/Setting";
// Worker Routes

import Worker from "./Worker/Worker";
import WorkerMyAccount from "./Worker/Pages/MyAccount/MyAccount";
import AddRefund from "./Worker/Pages/MyAccount/AddRefund";
import EditRefund from "./Worker/Pages/MyAccount/EditRefund";
import RefundClaim from "./Worker/Pages/MyAccount/RefundClaim";

import WorkerDashboard from "./Worker/WorkerDashboard";
import WorkerTotalJobs from "./Worker/Pages/Job/WorkerTotalJobs";
import WorkerViewJob from "./Worker/Pages/Job/WorkerViewJob";
import HearingInvitation from "./Admin/Components/Workers/HearingInvitationForm";
import DisplayClaims from "./Admin/Components/Workers/DisplayClaims";
import Hearing from "./Worker/Pages/Hearing/Hearing";
import Protocol from "./Worker/Pages/Protocol/Protocol";
import Availability from "./Worker/Pages/Availability/Availability";
import NotAvailability from "./Worker/Pages/Availability/NotAvailability";
import SickLeaves from "./Worker/Pages/MyAccount/SickLeaves";
import AddLeaves from "./Worker/Pages/MyAccount/AddLeaves";
import EditLeaves from "./Worker/Pages/MyAccount/EditLeaves";
import AdvanceLoan from "./Worker/Pages/MyAccount/AdvanceLoan";

// Admin Routes
import Admin from "./Admin/Admin";
import AdminDashboard from "./Admin/Dashboard";
import TotalJobs from "./Admin/Pages/Jobs/TotalJobs";
// import ChangeWorkerRequestList from "./Admin/Pages/Jobs/ChangeWorkerRequests/ChangeWorkerRequestList";
import CreateJob from "./Admin/Pages/Jobs/CreateJob";
import CreateClientJob from "./Admin/Pages/Jobs/CreateClientJob";
import ChangeWorker from "./Admin/Pages/Jobs/ChangeWorker";
import ViewJob from "./Admin/Pages/Jobs/ViewJob";
import Leads from "./Admin/Pages/Lead/Lead";
import AddLead from "./Admin/Pages/Lead/AddLead";
import EditLead from "./Admin/Pages/Lead/EditLead";
import ViewLead from "./Admin/Pages/Lead/ViewLead";
import Clients from "./Admin/Pages/Clients/Client";
import AddClient from "./Admin/Pages/Clients/AddClient";
import AddLeadClient from "./Admin/Pages/Clients/AddLeadClient";
import EditClient from "./Admin/Pages/Clients/EditClient";
import ViewClient from "./Admin/Pages/Clients/ViewClient";
import AllWorkers from "./Admin/Pages/Workers/AllWorkers";
import FreezeShiftWorkers from "./Admin/Pages/Workers/FreezeShiftWorkers";
import AddWorker from "./Admin/Pages/Workers/AddWorker";
import EditWorker from "./Admin/Pages/Workers/EditWorker";
import WorkersLeave from "./Admin/Pages/Workers/WorkersLeave";
import WorkersRefund from "./Admin/Pages/Workers/WorkersRefund";
import WorkerTermination from "./Admin/Components/Workers/WorkerTermination";
import WorkersHearing from "./Admin/Components/Workers/WorkersHearing";
import ViewHearing from "./Admin/Pages/Hearing/ViewHearing";
import Claim from "./Admin/Components/Workers/Claim";
import HearingProtocol from "./Admin/Components/Workers/HearingProtocol";
import ViewWorker from "./Admin/Pages/Workers/ViewWorker";
import ViewWorkerContract from "./Admin/Pages/Workers/WorkerContract";
import AdminLogin from "./Admin/Pages/Auth/AdminLogin";
import Setting from "./Admin/Pages/Setting/Setting";
// import Credentials from "./Admin/Pages/Setting/Credentials";
import ManageTeam from "./Admin/Pages/Setting/ManageTeam";
import AddTeam from "./Admin/Pages/Setting/AddTeam";
import EditTeam from "./Admin/Pages/Setting/EditTeam";
import Services from "./Admin/Pages/Services/Services";
import AddService from "./Admin/Pages/Services/AddService";
import EditService from "./Admin/Pages/Services/EditService";
import ViewService from "./Admin/Pages/Services/ViewService";
import ServiceSchedule from "./Admin/Pages/Services/ServiceSchedule";
import AddServiceSchedule from "./Admin/Pages/Services/AddServiceSchedule";
import EditServiceSchedule from "./Admin/Pages/Services/EditServiceSchedule";
import OfferPrice from "./Admin/Pages/OfferPrice/OfferPrice";
import AddOffer from "./Admin/Pages/OfferPrice/AddOffer";
import EditOffer from "./Admin/Pages/OfferPrice/EditOffer";
import ViewOffer from "./Admin/Pages/OfferPrice/ViewOffer";
import Contract from "./Admin/Pages/Contract/Contract";
import AddContract from "./Admin/Pages/Contract/AddContract";
import EditContract from "./Admin/Pages/Contract/EditContract";
import ViewContract from "./Admin/Pages/Contract/ViewContract";
import Error404 from "./Error404";
import WorkerLogin from "./Worker/Auth/WorkerLogin";
import ClientLogin from "./Client/Auth/ClientLogin";
import Schedule from "./Admin/Pages/Schedule/Schedule";
import ViewSchedule from "./Admin/Pages/Schedule/ViewSchedule";
import PriceOffer from "./Pages/PriceOffer";
import InsuranceEng from "./Pages/Insurance/InsuranceEng";
import InsuranceHeb from "./Pages/Insurance/InsuranceHeb";
import WorkContract from "./Pages/WorkContract";
import MeetingStatus from "./Pages/MeetingStatus";
import WorkerJobDetails from "./Pages/WorkerJobDetails";
import CalendarTeam from "./Pages/CalendarTeam";
import Thankyou from "./Pages/Thankyou";
import ThankYouHearingSchedule from "./Pages/ThankYouHearingSchedule";
import ManageTime from "./Admin/Pages/Setting/Time/ManageTime";
import AddTime from "./Admin/Pages/Setting/Time/AddTime";
import EditTime from "./Admin/Pages/Setting/Time/EditTime";

import ServiceTemplate from "./Admin/Pages/Services/Templates";
import RegularServiceTemplate from "./Pages/offertemplates/template_regular";
import OfficeCleaningTemplate from "./Pages/offertemplates/template_officeCleaning";
import AfterRenovationTemplate from "./Pages/offertemplates/template_cleaningAfterRenovation";
import ThoroughCleaningTemplate from "./Pages/offertemplates/template_throughoutCleaning";
import TemplateWindowCleaning from "./Pages/offertemplates/template_windowCleaning";
import TemplateAirbnbCleaning from "./Pages/offertemplates/template_airbnbCleaning";
import TemplateOthers from "./Pages/offertemplates/template_others";

import WorkerContract from "./Pages/WorkerContract";
import Form101 from "./Pages/Form101";
import Languages from "./Admin/Pages/Languages/language";
import EditLanguages from "./Admin/Pages/Languages/EditLanguage";
import Notification from "./Admin/Pages/Notification/Notification";
import Income from "./Admin/Pages/Income";
// import Invoices from "./Admin/Pages/Sales/Invoices/Invoices";
// import AddInvoice from "./Admin/Pages/Sales/Invoices/AddInvoice";
// import Orders from "./Admin/Pages/Sales/Orders/Orders";
import AddOrder from "./Admin/Pages/Sales/Orders/AddOrder";
import Payments from "./Admin/Pages/Payment/Payments";
import ScheduleMeet from "./Pages/ScheduleMeet";
import Chat from "./Admin/Pages/Chat/chat";
import Responses from "./Admin/Pages/Chat/responses";
import Messenger from "./Admin/Pages/Chat/messenger";
import MeetingFiles from "./Pages/MeetingFIles";
import MeetingSchedule from "./Pages/MeetingSchedule";
import Availibility from "./Pages/TeamMembers/Availibility";
import ChangeSchedule from "./Client/Pages/Jobs/ChangeSchedule";
import ChangeShift from "./Admin/Pages/Jobs/ChangeShift";
import WorkerHours from "./Admin/Pages/Workers/WorkerHours";
import Invoices from "./Client/Pages/Invoices/Invoices";
import ManpowerCompanies from "./Admin/Pages/ManpowerCompanies/ManpowerCompanies";
import WorkerAffectedAvailability from "./Admin/Pages/Workers/WorkerAffectedAvailability";
import SafeAndGear from "./Admin/Pages/safeAndGear/SafeAndGear";
import ReviewJob from "./Client/Pages/Jobs/ReviewJob";
import InsuranceForm from "./Pages/InsuranceForm";
// const SafeAndGear = lazy(() => import('./Admin/Pages/safeAndGear/SafeAndGear'))
// const ReviewJob = lazy(() => import('./Client/Pages/Jobs/ReviewJob'))
// const InsuranceForm = lazy(() => import('./Pages/InsuranceForm'))
// const WorkerForm = lazy(() => import('./Pages/WorkerForm'))
import WorkerForm from "./Pages/WorkerForm";
import WorkerInvitationForm from "./Pages/WorkerInvitationForm";
import AdminLoginOtp from "./Admin/Pages/Auth/AdminLoginOtp";
import WorkerLoginOtp from "./Worker/Auth/WorkerLoginOtp";
import ClientLoginOtp from "./Client/Auth/ClientLoginOtp";
import Holidays from "./Admin/Pages/Setting/Holiday";
import PayslipSettings from "./Admin/Pages/Setting/PayslipSettings";
import Board from "./Admin/Pages/TaskManagement/Board";
import Tasks from "./Worker/Pages/MyAccount/Tasks";
import TeamButtons from "./Pages/TeamButtons";
import TeamBtnsAfter7days from "./Pages/TeamBtnsAfter7days";
import { ExtraLinks } from "./Pages/ExtraLinks";
import TeamSkippedComments from "./Pages/TeamSkippedComments";
import { TimeManage } from "./Pages/TimeManage";
import Templates from "./Admin/Pages/Setting/Templates";
import AllTemplatesList from "./Admin/Pages/Setting/AllTemplatesList";
import Holiday from "./Admin/Pages/Setting/Holiday";
import AddHoliday from "./Admin/Pages/Setting/AddHoliday";
import EditHoliday from "./Admin/Pages/Setting/EditHoliday";
import WorkerLead from "./Admin/Pages/Workers/WorkerLead";
import WorkerLeadView from "./Admin/Pages/Workers/WorkerLeadView";
// import ChangeWorkerRequest from "./Client/Pages/Jobs/ChangeWorkerRequest";
import { RequestToChangeScheduled } from "./Pages/RequestToChangeScheduled";
import ScheduleChange from "./Admin/Components/Dashboard/ScheduleChange";
import Expanses from "./Admin/Components/Dashboard/Expanses";
import ScheduleRequestDetails from "./Pages/ScheduleRequestDetails";
import ManpowerSaftyForm from "./Pages/ManpowerSaftyForm";
import ManpowerDetailForm from "./Pages/ManpowerDetailForm";
import AllForms from "./Pages/Form101/AllForms";
import ChangePassword from "./Client/Auth/ChangePassword";
import ClientPropertyAdress from "./Client/ClientPropertyAdress";
import ViewPropertyAddress from "./Client/ViewPropertyAddress";
import FacebookInsights from "./Admin/Components/Dashboard/FacebookInsights";
import CustomMessage from "./Admin/Pages/Setting/CustomMessage";
import AdminDocument from "./Admin/Components/Documents/AdminDocument";
import ClientForgotPassword from "./Client/Auth/ClientForgotPassword";
import AddScheduleRequest from "./Admin/Components/Dashboard/AddScheduleRequest";
import InsuranceCompany from "./Admin/Pages/Setting/InsuranceCompany";
import ContactManager from "./Pages/ContactManager";
import OnMyWayJob from "./Pages/OnMyWayJob";
import FinishJobByWorker from "./Pages/FinishJobByWorker";
import WorkerForgetPassword from "./Admin/Pages/Workers/WorkerForgetPassword";
import AdminForgetPassword from "../js/Admin/Pages/Auth/AdminForgetPassword"
import Conflicts from "./Admin/Pages/Jobs/Conflicts";
import Contracts from "./Pages/Contracts";
import Discount from "./Admin/Pages/Setting/Discount";
import WorkerTutorial from "./Worker/Pages/MyAccount/WorkerTutorial";
import AdminTimerLogs from "./Admin/Pages/Setting/AdminTimerLogs";

// const ManpowerSaftyForm = lazy(() => import('./Pages/ManpowerSaftyForm'));
// const AllForms = lazy(() => import('./Pages/Form101/AllForms'))

TimeAgo.addDefaultLocale(en);
const options = {
    timeout: 2000,
    position: positions.TOP_RIGHT,
};

export default function MyRoutes() {

    return (
        <Provider template={AlertTemplate} {...options}>
            <Router future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
                <Routes>
                    {/* Home route  */}
                    <Route exact path="/" element={<ClientLogin />} />
                    <Route
                        exact
                        path="client/login"
                        element={<ClientLogin />}
                    />
                    <Route
                        exact
                        path="client/reset-password"
                        element={<ClientForgotPassword />}
                    />
                    <Route
                        exact
                        path="client/change-password"
                        element={<ChangePassword />}
                    />
                    <Route
                        exact
                        path="client/login-otp"
                        element={<ClientLoginOtp />}
                    />
                    <Route
                        exact
                        path="worker/login"
                        element={<WorkerLogin />}
                    />
                    <Route
                        exact
                        path="worker/reset-password"
                        element={<WorkerForgetPassword />}
                    />
                    <Route
                        exact
                        path="meeting-status/:id/reschedule"
                        element={<MeetingStatus />}
                    />
                    <Route
                        exact
                        path="meeting-files/:id"
                        element={<MeetingFiles />}
                    />
                    <Route
                        exact
                        path="team-btn/:id"
                        element={<TeamButtons />}
                    />
                    <Route
                        exact
                        path="team-btn7days/:id"
                        element={<TeamBtnsAfter7days />}
                    />
                    <Route
                        exact
                        path="time-manage/:id"
                        element={<TimeManage />}
                    />
                    <Route
                        exact
                        path="action-comment/:id"
                        element={<TeamSkippedComments />}
                    />

                    <Route
                        exact
                        path="confirmation/:id"
                        element={<ExtraLinks />}
                    />
                    <Route
                        exact
                        path="team-btn/:id"
                        element={<TeamButtons />}
                    />
                    <Route
                        exact
                        path="time-manage/:id"
                        element={<TimeManage />}
                    />
                    {/* <Route
                        exact
                        path="action-comment/:id"
                        element={<TeamSkippedComments />}
                    /> */}
                    {/* <Route
                        exact
                        path="contact-manager/:id"
                        element={<ContactManager />}
                    /> */}
                    <Route
                        exact
                        path="meeting-schedule/:id"
                        element={<MeetingSchedule />}
                    />
                    <Route
                        exact
                        path="price-offer/:id"
                        element={<PriceOffer />}
                    />
                    <Route
                        exact
                        path="insurance-eng"
                        element={<InsuranceEng />}
                    />
                    <Route
                        exact
                        path="insurance-heb"
                        element={<InsuranceHeb />}
                    />
                    <Route
                        exact
                        path="work-contract/:id"
                        element={<Contracts />}
                    />
                    <Route
                        exact
                        path="work-contract/:id/:hash"
                        element={<WorkContract />}
                    />
                    <Route exact path="form101/:id" element={<Form101 />} />
                    <Route
                        exact
                        path="form101/:id/:formId"
                        element={<Form101 />}
                    />
                    <Route
                        exact
                        path="forms/:id"
                        element={<AllForms />}
                    />
                    <Route
                        exact
                        path="forms/:id/:formId"
                        element={<AllForms />}
                    />
                    <Route
                        exact
                        path="worker-contract/:id"
                        element={<WorkerContract />}
                    />
                    <Route
                        exact
                        path="worker-safe-gear/:id"
                        element={<SafeAndGear />}
                    />
                    <Route
                        exact
                        path="insurance-form/:id"
                        element={<InsuranceForm />}
                    />
                    <Route
                        exact
                        path="manpower-safty-form/:id"
                        element={<ManpowerSaftyForm />}
                    />
                    <Route
                        exact
                        path="manpower-detail-form/:id"
                        element={<ManpowerDetailForm />}
                    />
                    <Route exact path="calendar" element={<CalendarTeam />} />
                    <Route
                        exact
                        path="thankyou/:id/:response"
                        element={<Thankyou />}
                    />
                    <Route
                        exact
                        path="worker/jobs/:uuid"
                        element={<ContactManager />}
                    />
                    <Route
                        exact
                        path="worker/jobs/on-my-way/:uuid"
                        element={<OnMyWayJob />}
                    />
                    <Route
                        exact
                        path="worker/jobs/finish/:uuid"
                        element={<FinishJobByWorker />}
                    />
                    <Route
                        exact
                        path="/hearing-schedule/:id"
                        element={<ThankYouHearingSchedule />}
                    />
                    <Route
                        exact
                        path="schedule-meet/:id"
                        element={<ScheduleMeet />}
                    />
                    <Route
                        exact
                        path="worker/:wid/jobs/:jid/approve"
                        element={<WorkerJobDetails />}
                    />
                    <Route
                        exact
                        path="worker-forms/:id"
                        element={<WorkerForm />}
                    />
                    <Route
                        exact
                        path="worker-lead-forms/:id"
                        element={<WorkerForm />}
                    />
                    <Route
                        exact
                        path="worker-forms/:id/:formId"
                        element={<WorkerForm />}
                    />
                    <Route
                        exact
                        path="worker-invitation-form/:id"
                        element={<WorkerInvitationForm />}
                    />

                    <Route
                        exact
                        path="/request-to-change/:id"
                        element={<RequestToChangeScheduled />}
                    />

                    {/* Client Routes Start  */}

                    <Route element={<ClientProtectedRoutes />}>
                        <Route path="client" element={<Client />}>
                            <Route
                                exact
                                path="dashboard"
                                element={<ClientDashboard />}
                            />
                            <Route
                                exact
                                path="schedule"
                                element={<ClientSchedules />}
                            />
                            <Route
                                exact
                                path="/client/offered-price"
                                element={<ClientOffers />}
                            />
                            <Route
                                exact
                                path="/client/view-offer/:id"
                                element={<ClientViewOffer />}
                            />
                            <Route
                                exact
                                path="/client/contracts"
                                element={<ClientContracts />}
                            />
                            <Route
                                exact
                                path="/client/view-contract/:id/:hash"
                                element={<ClientViewContract />}
                            />
                            <Route
                                exact
                                path="/client/files/:meetId"
                                element={<ClientFiles />}
                            />
                            <Route
                                exact
                                path="/client/jobs"
                                element={<ClientJobs />}
                            />
                            <Route
                                exact
                                path="/client/invoices"
                                element={<Invoices />}
                            />
                            <Route
                                exact
                                path="/client/jobs/view/:id"
                                element={<ClientJobView />}
                            />
                            <Route
                                exact
                                path="/client/jobs/:id/review"
                                element={<ReviewJob />}
                            />
                            <Route
                                exact
                                path="/client/jobs/:id/change-schedule"
                                element={<ChangeSchedule />}
                            />
                            <Route
                                exact
                                path="/client/property-addresses"
                                element={<ClientPropertyAdress />}
                            />
                            <Route
                                exact
                                path="/client/property-address/:id"
                                element={<ViewPropertyAddress />}
                            />
                            {/* <Route
                                exact
                                path="/client/jobs/:id/change-worker-request"
                                element={<ChangeWorkerRequest />}
                            /> */}
                            <Route
                                exact
                                path="/client/settings"
                                element={<ClientSetting />}
                            />
                        </Route>
                    </Route>
                    {/* Client Routes End  */}

                    {/* Worker Routes Start  */}
                    <Route
                        exact
                        path="worker/login"
                        element={<WorkerLogin />}
                    />
                    <Route
                        exact
                        path="worker/login-otp"
                        element={<WorkerLoginOtp />}
                    />

                    <Route path="worker" element={<Worker />}></Route>
                    <Route element={<WorkerProtectedRoutes />}>
                        <Route path="worker" element={<Worker />}>
                            <Route
                                exact
                                path="my-account"
                                element={<WorkerMyAccount />}
                            />
                            <Route
                                exact
                                path="dashboard"
                                element={<WorkerDashboard />}
                            />
                            <Route
                                exact
                                path="jobs"
                                element={<WorkerTotalJobs />}
                            />
                            <Route
                                exact
                                path="tutorial"
                                element={<WorkerTutorial />}
                            />
                            <Route
                                exact
                                path="hearing"
                                element={<Hearing />}
                            />
                            <Route
                                exact
                                path="protocol"
                                element={<Protocol />}
                            />
                            <Route
                                exact
                                path="leaves"
                                element={<SickLeaves />}
                            />
                            {/* <Route
                                exact
                                path="sick-leaves/:id/edit"
                                element={<EditLeaves />}
                            /> */}
                            <Route
                                exact
                                path="refund-claim"
                                element={<RefundClaim />}
                            />
                            <Route
                                exact
                                path="refund-claim/create"
                                element={<AddRefund />}
                            />
                            <Route
                                exact
                                path="refund-claim/:id/edit"
                                element={<EditRefund />}
                            />
                            <Route
                                exact
                                path="jobs/view/:id"
                                element={<WorkerViewJob />}
                            />
                            <Route
                                exact
                                path="schedule"
                                element={<Availability />}
                            />
                            <Route
                                exact
                                path="not-available"
                                element={<NotAvailability />}
                            />
                            <Route
                                exact
                                path="sick-leaves/create"
                                element={<AddLeaves />}
                            />
                            <Route
                                exact
                                path="sick-leaves/:id/edit"
                                element={<EditLeaves />}
                            />
                            <Route
                                exact
                                path="advance-loan"
                                element={<AdvanceLoan />}
                            />
                            <Route
                                exact
                                path="tasks"
                                element={<Tasks />}
                            />
                        </Route>
                    </Route>
                    {/* Worker Routes End  */}

                    {/* Admin Routes Start  */}
                    <Route exact path="/admin/login" element={<AdminLogin />} />
                    <Route
                        exact
                        path="/admin/reset-password"
                        element={<AdminForgetPassword />}
                    />
                    <Route exact path="/admin/login-otp" element={<AdminLoginOtp />} />
                    <Route element={<AdminProtectedRoutes />}>
                        <Route path="admin" element={<Admin />}>
                            <Route
                                exact
                                path="dashboard"
                                element={<AdminDashboard />}
                            />
                            <Route exact path="jobs" element={<TotalJobs />} />
                            {/* <Route
                                exact
                                path="jobs/change-worker-requests"
                                element={<ChangeWorkerRequestList />}
                            /> */}
                            <Route
                                exact
                                path="conflicts"
                                element={<Conflicts />}
                            />
                            <Route
                                exact
                                path="schedule-requests"
                                element={<ScheduleChange />}
                            />
                            <Route
                                exact
                                path="expanses"
                                element={<Expanses />}
                            />
                            <Route
                                exact
                                path="add-schedule-requests"
                                element={<AddScheduleRequest />}
                            />
                            <Route
                                exact
                                path="documents"
                                element={<AdminDocument />}
                            />
                            <Route
                                exact
                                path="facebook-insights"
                                element={<FacebookInsights />}
                            />
                            <Route
                                exact
                                path="schedule-requests/:id"
                                element={<ScheduleRequestDetails />}
                            />
                            <Route
                                exact
                                path="create-job/:id"
                                element={<CreateJob />}
                            />
                            <Route
                                exact
                                path="create-client-job/:id"
                                element={<CreateClientJob />}
                            />
                            <Route
                                exact
                                path="jobs/:id/change-worker"
                                element={<ChangeWorker />}
                            />
                            {/* <Route
                                exact
                                path="jobs/:id/change-shift"
                                element={<ChangeShift />}
                            /> */}
                            <Route
                                exact
                                path="jobs/view/:id"
                                element={<ViewJob />}
                            />
                            <Route exact path="leads" element={<Leads />} />
                            <Route
                                exact
                                path="leads/create"
                                element={<AddLead />}
                            />
                            <Route
                                exact
                                path="leads/:id/edit"
                                element={<EditLead />}
                            />
                            <Route
                                exact
                                path="leads/view/:id"
                                element={<ViewLead />}
                            />
                            <Route exact path="clients" element={<Clients />} />
                            <Route
                                exact
                                path="clients/create"
                                element={<AddClient />}
                            />
                            <Route
                                exact
                                path="add-lead-client/:id"
                                element={<AddLeadClient />}
                            />
                            <Route
                                exact
                                path="clients/:id/edit"
                                element={<EditClient />}
                            />
                            <Route
                                exact
                                path="clients/view/:id"
                                element={<ViewClient />}
                            />
                            <Route
                                exact
                                path="workers"
                                element={<AllWorkers />}
                            />
                            <Route
                                exact
                                path="worker-leads"
                                element={<WorkerLead />}
                            />
                            <Route
                                exact
                                path="worker-leads/add"
                                element={<WorkerLeadView mode="add" />}
                            />
                            <Route
                                exact
                                path="worker-leads/view/:id"
                                element={<WorkerLeadView mode="view" />}
                            />
                            <Route
                                exact
                                path="worker-leads/edit/:id"
                                element={<WorkerLeadView mode="edit" />}
                            />

                            <Route
                                exact
                                path="workers/working-hours"
                                element={<WorkerHours />}
                            />
                            <Route
                                exact
                                path="workers/freeze-shift/:id"
                                element={<FreezeShiftWorkers />}
                            />
                            <Route
                                exact
                                path="add-worker"
                                element={<AddWorker />}
                            />
                            <Route
                                exact
                                path="workers/edit/:id"
                                element={<EditWorker />}
                            />
                            <Route
                                exact
                                path="workers/view/:id"
                                element={<ViewWorker />}
                            />
                            <Route
                                exact
                                path="workers/view/:id/hearing-invitation"
                                element={<HearingInvitation />}
                            />
                            <Route
                                exact
                                path="workers/view/:id/show-claims"
                                element={<DisplayClaims />}
                            />
                            <Route
                                exact
                                path="workers/view/:id/upload-claim"
                                element={<HearingProtocol />}
                            />
                            <Route
                                exact
                                path="workers/view/:workerId/hearing-invitation/:hid/create-claim"
                                element={<Claim />}
                            />
                            <Route
                                exact
                                path="workers/view/:id/upload-claim"
                                element={<HearingProtocol />}
                            />
                            <Route
                                exact
                                path="worker-contract/:id"
                                element={<ViewWorkerContract />}
                            />
                            <Route
                                exact
                                path="settings"
                                element={<Setting />}
                            />
                            {/* <Route
                                exact
                                path="credentials"
                                element={<Credentials />}
                            /> */}
                            <Route
                                exact
                                path="manage-team"
                                element={<ManageTeam />}
                            />
                            <Route
                                exact
                                path="manage-team/timer-logs/:id"
                                element={<AdminTimerLogs />}
                            />
                            <Route
                                exact
                                path="templates"
                                element={<AllTemplatesList />}
                            />
                            <Route
                                exact
                                path="templates/edit/template/:id"
                                element={<Templates />}
                            />
                            <Route
                                exact
                                path="templates"
                                element={<AllTemplatesList />}
                            />
                            <Route
                                exact
                                path="custom-message"
                                element={<CustomMessage />}
                            />
                            <Route
                                exact
                                path="templates/edit/template/:id"
                                element={<Templates />}
                            />
                            <Route
                                exact
                                path="teams/create"
                                element={<AddTeam />}
                            />
                            <Route
                                exact
                                path="teams/:id/edit"
                                element={<EditTeam />}
                            />
                            <Route
                                exact
                                path="my-availability"
                                element={<Availibility />}
                            />
                            <Route
                                exact
                                path="manage-team/team-member/availability/:id"
                                element={<Availibility />}
                            />
                            <Route
                                exact
                                path="services"
                                element={<Services />}
                            />
                            <Route
                                exact
                                path="manpower-companies"
                                element={<ManpowerCompanies />}
                            />
                            <Route
                                exact
                                path="insurance-companies"
                                element={<InsuranceCompany />}
                            />
                            <Route
                                exact
                                path="discount"
                                element={<Discount />}
                            />
                            <Route
                                exact
                                path="holidays"
                                element={<Holidays />}
                            />
                            <Route
                                exact
                                path="holidays/create"
                                element={<AddHoliday />}
                            />
                            <Route
                                exact
                                path="holidays/:id/edit"
                                element={<EditHoliday />}
                            />
                            <Route
                                exact
                                path="payslip-settings"
                                element={<PayslipSettings />}
                            />
                            <Route
                                exact
                                path="task"
                                element={<Board />}
                            />
                            <Route
                                exact
                                path="workers-leaves"
                                element={<WorkersLeave />}
                            />
                            <Route

                                path="workers-refund"
                                element={<WorkersRefund />}
                            />
                            <Route
                                exact
                                path="workers/view/:workerId"
                                element={<WorkerTermination />}
                            />
                            <Route
                                exact
                                path="workers/view/:workerId/hearing-invitation"
                                element={<WorkersHearing />}
                            />
                            <Route
                                exact
                                path="workers/view/:workerId/hearing-invitation/:hid"
                                element={<ViewHearing />}
                            />
                            <Route
                                exact
                                path="holidays"
                                element={<Holiday />}
                            />
                            <Route
                                exact
                                path="holidays/create"
                                element={<AddHoliday />}
                            />
                            <Route
                                exact
                                path="holidays/:id/edit"
                                element={<EditHoliday />}
                            />
                            <Route
                                exact
                                path="holidays"
                                element={<Holiday />}
                            />
                            <Route
                                exact
                                path="holidays/create"
                                element={<AddHoliday />}
                            />
                            <Route
                                exact
                                path="holidays/:id/edit"
                                element={<EditHoliday />}
                            />
                            <Route
                                exact
                                path="services/create"
                                element={<AddService />}
                            />
                            <Route
                                exact
                                path="services/:id/edit"
                                element={<EditService />}
                            />
                            <Route
                                exact
                                path="services/:id"
                                element={<ViewService />}
                            />
                            <Route
                                exact
                                path="service-schedules"
                                element={<ServiceSchedule />}
                            />
                            <Route
                                exact
                                path="service-schedules/create"
                                element={<AddServiceSchedule />}
                            />
                            <Route
                                exact
                                path="service-schedules/:id/edit"
                                element={<EditServiceSchedule />}
                            />
                            <Route
                                exact
                                path="service-templates"
                                element={<ServiceTemplate />}
                            />
                            <Route
                                exact
                                path="template/regular-service"
                                element={<RegularServiceTemplate />}
                            />
                            <Route
                                exact
                                path="template/office-cleaning"
                                element={<OfficeCleaningTemplate />}
                            />
                            <Route
                                exact
                                path="template/after-renovation"
                                element={<AfterRenovationTemplate />}
                            />
                            <Route
                                exact
                                path="template/thorough-cleaning"
                                element={<ThoroughCleaningTemplate />}
                            />
                            <Route
                                exact
                                path="template/window-cleaning"
                                element={<TemplateWindowCleaning />}
                            />
                            <Route
                                exact
                                path="template/airbnb-servce"
                                element={<TemplateAirbnbCleaning />}
                            />
                            <Route
                                exact
                                path="template/others"
                                element={<TemplateOthers />}
                            />
                            <Route
                                exact
                                path="offered-price"
                                element={<OfferPrice />}
                            />
                            <Route
                                exact
                                path="offers/create"
                                element={<AddOffer />}
                            />
                            <Route
                                exact
                                path="offered-price/edit/:id"
                                element={<EditOffer />}
                            />
                            <Route
                                exact
                                path="view-offer/:id"
                                element={<ViewOffer />}
                            />
                            <Route
                                exact
                                path="contracts"
                                element={<Contract />}
                            />
                            <Route
                                exact
                                path="add-contract"
                                element={<AddContract />}
                            />
                            <Route
                                exact
                                path="edit-contract"
                                element={<EditContract />}
                            />
                            <Route
                                exact
                                path="view-contract/:id"
                                element={<ViewContract />}
                            />
                            <Route
                                exact
                                path="worker-affected-availability/:id"
                                element={<WorkerAffectedAvailability />}
                            />
                            <Route
                                exact
                                path="schedule"
                                element={<Schedule />}
                            />
                            <Route
                                exact
                                path="schedule/view/:id"
                                element={<ViewSchedule />}
                            />
                            <Route
                                exact
                                path="manage-time"
                                element={<ManageTime />}
                            />
                            <Route
                                exact
                                path="add-time"
                                element={<AddTime />}
                            />
                            <Route
                                exact
                                path="edit-time/:id"
                                element={<EditTime />}
                            />
                            <Route
                                exact
                                path="notifications"
                                element={<Notification />}
                            />
                            <Route
                                exact
                                path="Languages"
                                element={<Languages />}
                            />
                            <Route
                                exact
                                path="edit-language/:id"
                                element={<EditLanguages />}
                            />
                            <Route exact path="income" element={<Income />} />
                            {/* <Route
                                exact
                                path="invoices"
                                element={<Invoices />}
                            /> */}
                            {/* <Route
                                exact
                                path="add-invoice"
                                element={<AddInvoice />}
                            /> */}
                            <Route
                                exact
                                path="add-order"
                                element={<AddOrder />}
                            />
                            {/* <Route exact path="orders" element={<Orders />} /> */}
                            <Route
                                exact
                                path="payments"
                                element={<Payments />}
                            />
                            <Route
                                exact
                                path="chat"
                                element={<Chat key="chat" number={process.env.MIX_TWILIO_WHATSAPP_NUMBER} />}
                            />
                            <Route
                                exact
                                path="worker-lead-chat"
                                element={<Chat key="worker-lead" number={null} workerLead={true} />}
                            />
                            <Route
                                exact
                                path="whapi-chat"
                                element={<Chat key="whapi-chat" number={process.env.MIX_WHAPI_NUMBER} />}
                            />
                            <Route
                                exact
                                path="responses"
                                element={<Responses />}
                            />
                            <Route
                                exact
                                path="messenger"
                                element={<Messenger />}
                            />
                        </Route>
                    </Route>
                    {/* Admin Routes End  */}

                    {/* Error 404 Page / Not Found */}

                    <Route path="*" element={<Error404 />} />
                </Routes>
            </Router>
        </Provider>
    );
}
