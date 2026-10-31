class EmployeeProfile {
  final EmployeeProfileEmployee employee;
  final Compensation? compensation;
  final ShiftAssignment? shiftAssignment;
  final WorkLocationAssignment? workLocationAssignment;
  final Manager? manager;

  EmployeeProfile({
    required this.employee,
    this.compensation,
    this.shiftAssignment,
    this.workLocationAssignment,
    this.manager,
  });

  factory EmployeeProfile.fromJson(Map<String, dynamic> json) => EmployeeProfile(
        employee: EmployeeProfileEmployee.fromJson(json['employee'] ?? {}),
        compensation: json['compensation'] == null ? null : Compensation.fromJson(json['compensation']),
        shiftAssignment: json['shift_assignment'] == null ? null : ShiftAssignment.fromJson(json['shift_assignment']),
        workLocationAssignment: json['work_location_assignment'] == null ? null : WorkLocationAssignment.fromJson(json['work_location_assignment']),
        manager: json['manager'] == null ? null : Manager.fromJson(json['manager']),
      );
}

class EmployeeProfileEmployee {
  final int id;
  final String employeeNumber;
  final String fullName;
  final String? workEmail;
  final String? phoneE164;
  final String? jobTitle;
  final Division? division;
  final String employmentStatus;
  final bool faceRegistered;
  final String? faceRegisteredAt;

  EmployeeProfileEmployee({
    required this.id,
    required this.employeeNumber,
    required this.fullName,
    this.workEmail,
    this.phoneE164,
    this.jobTitle,
    this.division,
    required this.employmentStatus,
    required this.faceRegistered,
    this.faceRegisteredAt,
  });

  String get initials => fullName.split(' ').take(2).map((w) => w.isNotEmpty ? w[0] : '').join();

  factory EmployeeProfileEmployee.fromJson(Map<String, dynamic> json) => EmployeeProfileEmployee(
        id: json['id'] ?? 0,
        employeeNumber: json['employee_number'] ?? '',
        fullName: json['full_name'] ?? '',
        workEmail: json['work_email'],
        phoneE164: json['phone_e164'],
        jobTitle: json['job_title'],
        division: json['division'] == null ? null : Division.fromJson(json['division']),
        employmentStatus: json['employment_status'] ?? 'probation',
        faceRegistered: json['face_registered'] ?? false,
        faceRegisteredAt: json['face_registered_at'],
      );
}

class Division {
  final int id;
  final String name;
  Division({required this.id, required this.name});
  factory Division.fromJson(Map<String, dynamic> j) => Division(id: j['id'], name: j['name']);
}

class Compensation {
  final String baseSalary;
  final String transportAllowance;
  final String mealAllowance;
  final String totalMonthly;
  final String currency;
  Compensation({required this.baseSalary, required this.transportAllowance, required this.mealAllowance, required this.totalMonthly, required this.currency});
  factory Compensation.fromJson(Map<String, dynamic> j) => Compensation(
        baseSalary: j['base_salary']?.toString() ?? '0',
        transportAllowance: j['transport_allowance']?.toString() ?? '0',
        mealAllowance: j['meal_allowance']?.toString() ?? '0',
        totalMonthly: j['total_monthly']?.toString() ?? '0',
        currency: j['currency'] ?? 'IDR',
      );
}

class ShiftAssignment {
  final int shiftId;
  final String name;
  final bool isOvernight;
  final String effectiveFrom;
  ShiftAssignment({required this.shiftId, required this.name, required this.isOvernight, required this.effectiveFrom});
  factory ShiftAssignment.fromJson(Map<String, dynamic> j) => ShiftAssignment(
        shiftId: j['shift_id'],
        name: j['name'] ?? '',
        isOvernight: j['is_overnight'] ?? false,
        effectiveFrom: j['effective_from'] ?? '',
      );
}

class WorkLocationAssignment {
  final int workLocationId;
  final String name;
  final String effectiveFrom;
  WorkLocationAssignment({required this.workLocationId, required this.name, required this.effectiveFrom});
  factory WorkLocationAssignment.fromJson(Map<String, dynamic> j) => WorkLocationAssignment(
        workLocationId: j['work_location_id'],
        name: j['name'] ?? '',
        effectiveFrom: j['effective_from'] ?? '',
      );
}

class Manager {
  final int id;
  final String fullName;
  final String employeeNumber;
  Manager({required this.id, required this.fullName, required this.employeeNumber});
  factory Manager.fromJson(Map<String, dynamic> j) => Manager(id: j['id'], fullName: j['full_name'] ?? '', employeeNumber: j['employee_number'] ?? '');
}
