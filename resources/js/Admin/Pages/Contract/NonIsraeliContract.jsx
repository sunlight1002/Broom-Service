import React, { useRef } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { Table } from "react-bootstrap";
import * as yup from "yup";
import { useFormik } from "formik";
import TextField from "../../../Pages/Form101/inputElements/TextField";
import DateField from "../../../Pages/Form101/inputElements/DateField";
import SignatureCanvas from "react-signature-canvas";

const formSchema = yup.object({
    fullName: yup.string().trim().required("Full name is required"),
    role: yup.string().trim().required("Role is required"),
    IdNumber: yup
        .number()
        .typeError("invalid number")
        .required("ID Number is required"),
    Address: yup.string().trim().required("Address is required"),
    startDate: yup.date().required("Start date of job is required"),
    signatureDate1: yup.date().required("Date is required"),
    signatureDate2: yup.date().required("Date is required"),
    signatureDate3: yup.date().required("Date is required"),
    signatureDate4: yup.date().required("Date is required"),
    signature1: yup.mixed().required("Signature is required"),
    signature2: yup.mixed().required("Signature is required"),
    signature3: yup.mixed().required("Signature is required"),
    signature4: yup.mixed().required("Signature is required"),
    companySignature1: yup.mixed().required("Signature is required"),
    companySignature2: yup.mixed().required("Signature is required"),
});

export function NonIsraeliContract({ handleFormSubmit }) {
    const sigRef1 = useRef();
    const sigRef2 = useRef();
    const sigRef3 = useRef();
    const sigRef4 = useRef();
    const companySigRef1 = useRef();
    const companySigRef2 = useRef();
    const initialValues = {
        fullName: "",
        IdNumber: "",
        Address: "",
        startDate: "",
        signatureDate1: "",
        signatureDate2: "",
        signatureDate3: "",
        signatureDate4: "",
        signature1: "",
        signature2: "",
        signature3: "",
        signature4: "",
        companySignature1: "",
        companySignature2: "",
        role: "",
    };
    const {
        errors,
        touched,
        handleBlur,
        handleChange,
        handleSubmit,
        values,
        setFieldValue,
    } = useFormik({
        initialValues,
        validationSchema: formSchema,
        onSubmit: (values) => {
            handleFormSubmit(values);
        },
    });
    const handleSignatureEnd1 = () => {
        setFieldValue("signature1", sigRef1.current.toDataURL());
    };
    const clearSignature1 = () => {
        sigRef1.current.clear();
        setFieldValue("signature1", "");
    };
    const handleSignatureEnd2 = () => {
        setFieldValue("signature2", sigRef2.current.toDataURL());
    };
    const clearSignature2 = () => {
        sigRef2.current.clear();
        setFieldValue("signature2", "");
    };
    const handleSignatureEnd3 = () => {
        setFieldValue("signature3", sigRef3.current.toDataURL());
    };
    const clearSignature3 = () => {
        sigRef3.current.clear();
        setFieldValue("signature3", "");
    };
    const handleSignatureEnd4 = () => {
        setFieldValue("signature4", sigRef4.current.toDataURL());
    };
    const clearSignature4 = () => {
        sigRef4.current.clear();
        setFieldValue("signature4", "");
    };
    const handleCompanySignatureEnd1 = () => {
        setFieldValue("companySignature1", companySigRef1.current.toDataURL());
    };
    const clearCompanySignature1 = () => {
        companySigRef1.current.clear();
        setFieldValue("companySignature1", "");
    };
    const handleCompanySignatureEnd2 = () => {
        setFieldValue("companySignature2", companySigRef2.current.toDataURL());
    };
    const clearCompanySignature2 = () => {
        companySigRef2.current.clear();
        setFieldValue("companySignature2", "");
    };
    return (
        <div id="container">
            {/* <Sidebar /> */}
            <div id="content">
                <div className="w-75 mx-auto mt-5">
                    <form onSubmit={handleSubmit}>
                        <div className="text-center">
                            <h5>
                                <strong>
                                    <u>
                                        The employment agreement as well as a
                                        notice to the employee regarding
                                        working conditions
                                    </u>
                                </strong>
                            </h5>

                            <p className="mt-2">
                                In accordance with Section 1 of the Notice to
                                the Employee (Working Conditions) Law, 2002
                            </p>
                        </div>
                        <div>
                            <ol
                                className="mt-5 lh-lg "
                                style={{ fontSize: "16px" }}
                            >
                                <li>
                                    <strong>
                                        The name of the employer Brom Service
                                        L.M. Ltd. - private company number
                                        515184208 Maan Amal 11, Rosh Ha'Ein
                                    </strong>
                                    <div className="row gap-3">
                                        <div className="col-6">
                                            <TextField
                                                name={"fullName"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={"Employee's name"}
                                                value={values.fullName}
                                                required={true}
                                                error={
                                                    touched.fullName &&
                                                    errors.fullName
                                                }
                                            />
                                        </div>
                                        <div className="col-6">
                                            <TextField
                                                name={"IdNumber"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={"Passport Number"}
                                                value={values.IdNumber}
                                                required={true}
                                                error={
                                                    touched.IdNumber &&
                                                    errors.IdNumber
                                                }
                                            />
                                        </div>
                                    </div>
                                    <TextField
                                        name={"Address"}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        label={"Address"}
                                        value={values.Address}
                                        required={true}
                                        error={
                                            touched.Address && errors.Address
                                        }
                                    />
                                </li>
                                <li>
                                    <DateField
                                        name={"startDate"}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        label={"The date of the start of"}
                                        value={values.startDate}
                                        required={true}
                                        error={
                                            touched.startDate &&
                                            errors.startDate
                                        }
                                    />
                                    <p>
                                        The contract period is not fixed. The
                                        employee is hired as a new employee for
                                        all intents and purposes.
                                    </p>
                                </li>
                                <li>
                                    The main duties of the employee are the
                                    position:
                                    <TextField
                                        name={"role"}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        label={"Role"}
                                        value={values.role}
                                        required={true}
                                        error={touched.role && errors.role}
                                    />
                                    and any other job to which he will be
                                    assigned by the company. Each party reserves
                                    the right to terminate the placement and
                                    employment while giving advance notice to
                                    the other party in accordance with the Law
                                    on Advance Notice for Layoffs and
                                    Resignations of 2016
                                </li>
                                <li>
                                    The name of the employee's direct
                                    supervisor: Alex Kane
                                </li>
                                <li>
                                    <p>
                                        The basic salary of the cleaning workers
                                        will be calculated on the basis of
                                        minimum wage according to law, and in
                                        addition they will be given a bonus for
                                        persistence, which will supplement their
                                        salary to a basic wage of NIS 45 per
                                        hour plus social conditions, and this as
                                        a reward for persistence at work for 3
                                        full consecutive months in the company.
                                    </p>
                                    <ol>
                                        <li>
                                            If the employee decides to leave the
                                            job before the end of the foll
                                            three-month period, as agreed upon
                                            in this agreement, the employer will
                                            be entitled to deduct the amount of
                                            the bonus that exceeds the minimum
                                            wage paid to the employee, and even
                                            require the employee to return the
                                            amount of the bonus already paid to
                                            him during the first three months of
                                            work, and often by of deducting the
                                            excess amount from his salary or
                                            from any other payment due to him.
                                            <div className="row mt-5">
                                                <div className="col-4">
                                                    <p>
                                                        <strong>
                                                            The worker's
                                                            signature:*
                                                        </strong>
                                                    </p>
                                                    <SignatureCanvas
                                                        penColor="black"
                                                        canvasProps={{
                                                            className:
                                                                "sign101 border mt-1",
                                                        }}
                                                        ref={sigRef1}
                                                        onEnd={
                                                            handleSignatureEnd1
                                                        }
                                                    />
                                                    {touched.signature1 &&
                                                        errors.signature1 && (
                                                            <p className="text-danger">
                                                                {touched.signature1 &&
                                                                    errors.signature1}
                                                            </p>
                                                        )}

                                                    <div className="d-block">
                                                        <button
                                                            type="button"
                                                            className="btn btn-warning mb-2"
                                                            onClick={
                                                                clearSignature1
                                                            }
                                                        >
                                                            Clear
                                                        </button>
                                                    </div>
                                                </div>
                                                <div className="col-5"></div>
                                                <div className="col-3">
                                                    <DateField
                                                        name={"signatureDate1"}
                                                        onBlur={handleBlur}
                                                        onChange={handleChange}
                                                        label={"Date"}
                                                        value={
                                                            values.signatureDate1
                                                        }
                                                        required={true}
                                                        error={
                                                            touched.signatureDate1 &&
                                                            errors.signatureDate1
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        </li>
                                    </ol>
                                    <Table bordered className=" mt-3" size="sm">
                                        <thead className="text-center">
                                            <tr>
                                                <th colSpan={2}>
                                                    Payments that are not fixed
                                                </th>
                                                <th colSpan={2}>
                                                    Regular payments
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>Payment Date***</th>
                                                <th>Payment type**</th>
                                                <th>Payment Date***</th>
                                                <th>Payment type**</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>according to law</td>
                                                <td>Holiday</td>
                                                <td>9 per month </td>
                                                <td>Salary</td>
                                            </tr>
                                            <tr>
                                                <td>duly</td>
                                                <td>holidays</td>
                                                <td>9 per month</td>
                                                <td>travel</td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td>
                                                    9 per month in advance for
                                                    the annual payment
                                                </td>
                                                <td>recovery</td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td>
                                                    9 per month as part of the
                                                    basic salary{" "}
                                                </td>
                                                <td>seniority allowance</td>
                                            </tr>
                                        </tbody>
                                    </Table>
                                    <p>
                                        ** Detail types of payments such as:
                                        basic salary, equivalent to money -
                                        non-intoxicating food and beverages for
                                        consumption at the workplace and housing
                                        that are not reimbursement of expenses,
                                        seniority allowance, premiums and
                                        incentives, overtime; Additional shifts,
                                        recovery fees and any other payment in
                                        favor of wages whether it
                                        is fixed or not.
                                    </p>
                                    <p>
                                        Date & signature of the worker and his
                                        confirmation to the said
                                    </p>
                                    <p>
                                        *** If the payment date is not fixed, or
                                        the date will apply if a condition is
                                        met, this must be stated.
                                    </p>
                                    <div className="row mt-5">
                                        <div className="col-4">
                                            <p>
                                                <strong>
                                                    The worker's signature:*
                                                </strong>
                                            </p>
                                            <SignatureCanvas
                                                penColor="black"
                                                canvasProps={{
                                                    className:
                                                        "sign101 border mt-1",
                                                }}
                                                ref={sigRef2}
                                                onEnd={handleSignatureEnd2}
                                            />
                                            {touched.signature2 &&
                                                errors.signature2 && (
                                                    <p className="text-danger">
                                                        {touched.signature2 &&
                                                            errors.signature2}
                                                    </p>
                                                )}

                                            <div className="d-block">
                                                <button
                                                    type="button"
                                                    className="btn btn-warning mb-2"
                                                    onClick={clearSignature2}
                                                >
                                                    Clear
                                                </button>
                                            </div>
                                        </div>
                                        <div className="col-5"></div>
                                        <div className="col-3">
                                            <DateField
                                                name={"signatureDate2"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={"Date"}
                                                value={values.signatureDate2}
                                                required={true}
                                                error={
                                                    touched.signatureDate2 &&
                                                    errors.signatureDate2
                                                }
                                            />
                                        </div>
                                    </div>
                                </li>

                                <li>
                                    The length of the employee's normal working
                                    day - 8 hours in practice. The length of the
                                    employee's normal work week is 42 hours.
                                </li>
                                <li>
                                    The employee's weekly day of rest is
                                    Saturday.
                                </li>
                                <li>
                                    Payments for social conditions to which the
                                    employee is entitled:
                                    <Table bordered size="sm" className=" mt-3">
                                        <thead className="text-center">
                                            <tr>
                                                <th>Payment start date</th>
                                                <th>
                                                    % contributions from
                                                    employer
                                                </th>
                                                <th>% worker's allowances</th>
                                                <th>
                                                    The receiving body and the
                                                    name of the program
                                                </th>
                                                <th>Payment Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    or in the pay slip or at the
                                                    end of the work according to
                                                    the company's decision
                                                </td>
                                                <td>
                                                    Employer contributions
                                                    Directly to the employee 0
                                                    8.33% passed severance pay
                                                    subject to section 14 of the
                                                    Law on Severance Pay and in
                                                    accordance with the
                                                    expansion order in the
                                                    cleaning industry 7.5%
                                                    passed employer's benefits
                                                    either in the pay slip or at
                                                    the end of the job according
                                                    to the company's decision
                                                </td>
                                                <td>0</td>
                                                <td>
                                                    directly to the employee
                                                </td>
                                                <td>Employer contributions</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    or in the pay slip or at the
                                                    end of the work according to
                                                    the company's decision
                                                </td>
                                                <td>7.5% of wages</td>
                                                <td>0</td>
                                                <td>
                                                    directly to the employee
                                                </td>
                                                <td>Education fund</td>
                                            </tr>
                                        </tbody>
                                    </Table>
                                    <p>
                                        If the employer or an employers'
                                        organization of which the employer is a
                                        member, is a party to a collective
                                        agreement that regulates the employee's
                                        working conditions - the name of the
                                        workers' organization that is a party to
                                        that collective agreement is: the
                                        Jerusalem Chamber of Commerce.
                                    </p>
                                    <p>
                                        This notification is not an employment
                                        agreement, but the employer's
                                        notification regarding the main working
                                        conditions; Nothing in this notice is
                                        intended to derogate from any right
                                        granted to the employee by virtue of any
                                        law, extension order, collective
                                        agreement or employment contract.
                                    </p>
                                    <p>
                                        signature of the employee and his
                                        permission to write of the employer{" "}
                                    </p>
                                    <div className="row mt-5 gap-3">
                                        <div className="col-6">
                                            <p>
                                                <strong>
                                                    The worker's signature:*
                                                </strong>
                                            </p>
                                            <SignatureCanvas
                                                penColor="black"
                                                canvasProps={{
                                                    className:
                                                        "sign101 border mt-1",
                                                }}
                                                ref={sigRef3}
                                                onEnd={handleSignatureEnd3}
                                            />
                                            {touched.signature3 &&
                                                errors.signature3 && (
                                                    <p className="text-danger">
                                                        {touched.signature3 &&
                                                            errors.signature3}
                                                    </p>
                                                )}

                                            <div className="d-block">
                                                <button
                                                    type="button"
                                                    className="btn btn-warning mb-2"
                                                    onClick={clearSignature3}
                                                >
                                                    Clear
                                                </button>
                                            </div>
                                        </div>
                                        <div className="col-6">
                                            <p>
                                                <strong>
                                                    The Company's signature:*
                                                </strong>
                                            </p>
                                            <SignatureCanvas
                                                penColor="black"
                                                canvasProps={{
                                                    className:
                                                        "sign101 border mt-1",
                                                }}
                                                ref={companySigRef1}
                                                onEnd={
                                                    handleCompanySignatureEnd1
                                                }
                                            />
                                            {touched.companySignature1 &&
                                                errors.companySignature1 && (
                                                    <p className="text-danger">
                                                        {touched.companySignature1 &&
                                                            errors.companySignature1}
                                                    </p>
                                                )}

                                            <div className="d-block">
                                                <button
                                                    type="button"
                                                    className="btn btn-warning mb-2"
                                                    onClick={
                                                        clearCompanySignature1
                                                    }
                                                >
                                                    Clear
                                                </button>
                                            </div>
                                        </div>
                                        <div className="col-12">
                                            <DateField
                                                name={"signatureDate3"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={"Date"}
                                                value={values.signatureDate3}
                                                required={true}
                                                error={
                                                    touched.signatureDate3 &&
                                                    errors.signatureDate3
                                                }
                                            />
                                        </div>
                                    </div>
                                </li>
                            </ol>
                            <ol className="mt-5">
                                <li>
                                    <u>Deductions:</u>
                                    <ol>
                                        <li>
                                            The employer will deduct from the
                                            employee's salary for national
                                            insurance in accordance with the law
                                        </li>
                                        <li>
                                            The employer will deduct from the
                                            employee's salary for income tax in
                                            accordance with the law{" "}
                                        </li>
                                        <li>
                                            The employer will deduct from the
                                            employee's salary for deposits to
                                            foreign workers in accordance with
                                            the provisions of Section 11 of the
                                            Foreign Workers Law 5571-1991
                                        </li>
                                    </ol>
                                </li>
                                <li className="mt-3">
                                    <u>The employer's obligations:</u>
                                    <p>
                                        The employer is obliged to arrange, at
                                        his own expense, medical insurance for
                                        the employee for the entire period of
                                        his employment with him, in accordance
                                        with the provisions of Section 1d of the
                                        Foreign Workers Law 5511-1991, while
                                        deducting the amounts that can be
                                        deducted according to law.
                                    </p>
                                </li>
                                <li className="mt-3">
                                    <u> The supervisor of foreign workers:</u>
                                    <p>
                                        Details of the Commissioner for the
                                        Rights of Foreign Workers: Adv. Iris
                                        Maayan How to contact the Commissioner
                                        for Foreign Workers: Derech Shlomo
                                        (Selma) 53, Tel Aviv. Dew'. 03-7347230,
                                        fax. 03-7347269
                                    </p>
                                    <p>
                                        In accordance with section 1 of the
                                        Foreign Workers Law 1991-1991, any
                                        person may submit a written complaint to
                                        the Commissioner of Foreign Workers'
                                        Rights due to a violation of a provision
                                        under the Foreign Workers Law or failure
                                        to fulfill an obligation towards
                                        a foreign worker.
                                    </p>
                                </li>
                            </ol>
                            <div className="row mt-5 gap-3">
                                <div className="col-6">
                                    <p>
                                        <strong>
                                            The worker's signature:*
                                        </strong>
                                    </p>
                                    <SignatureCanvas
                                        penColor="black"
                                        canvasProps={{
                                            className: "sign101 border mt-1",
                                        }}
                                        ref={sigRef4}
                                        onEnd={handleSignatureEnd4}
                                    />
                                    {touched.signature4 &&
                                        errors.signature4 && (
                                            <p className="text-danger">
                                                {touched.signature4 &&
                                                    errors.signature4}
                                            </p>
                                        )}

                                    <div className="d-block">
                                        <button
                                            type="button"
                                            className="btn btn-warning mb-2"
                                            onClick={clearSignature4}
                                        >
                                            Clear
                                        </button>
                                    </div>
                                </div>
                                <div className="col-6">
                                    <p>
                                        <strong>
                                            The Company's signature:*
                                        </strong>
                                    </p>
                                    <SignatureCanvas
                                        penColor="black"
                                        canvasProps={{
                                            className: "sign101 border mt-1",
                                        }}
                                        ref={companySigRef2}
                                        onEnd={handleCompanySignatureEnd2}
                                    />
                                    {touched.companySignature2 &&
                                        errors.companySignature2 && (
                                            <p className="text-danger">
                                                {touched.companySignature2 &&
                                                    errors.companySignature2}
                                            </p>
                                        )}

                                    <div className="d-block">
                                        <button
                                            className="btn btn-warning mb-2"
                                            type="button"
                                            onClick={clearCompanySignature2}
                                        >
                                            Clear
                                        </button>
                                    </div>
                                </div>
                                <div className="col-12">
                                    <DateField
                                        name={"signatureDate4"}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        label={"Date"}
                                        value={values.signatureDate4}
                                        required={true}
                                        error={
                                            touched.signatureDate4 &&
                                            errors.signatureDate4
                                        }
                                    />
                                </div>
                            </div>
                        </div>
                        <button className="btn btn-success mt-3" type="submit">
                            Submit
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}
